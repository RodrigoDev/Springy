<?php
/** \file
 *  Springy.
 *
 *  \brief      Class used to create Model classes the access relational database tables.
 *  \copyright  ₢ 2007-2016 Fernando Val
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \note       Essa classe extende a classe DB.
 *  \version    2.0.2.41
 *  \ingroup    framework
 */
namespace Springy;

use Springy\DB\Conditions;
use Springy\DB\Where;
use Springy\Validation\Validator;

/**
 *  \brief Classe Model para acesso a banco de dados.
 *
 *  Esta classe extende a classe DB.
 *
 *  Esta classe deve ser utilizada como herança para as classes de acesso a banco.
 *
 *  Utilize-a para diminuir a quantidade de métodos que sua classe precisará ter para consultas e manutenção em bancos de dados.
 */
class Model extends DB implements \Iterator
{
    /**
     *  Atributos da classe.
     */
    /// Tabela utilizada pela classe
    protected $tableName = '';
    /// Relação de colunas da tabela para a consulta (pode ser uma string separada por vírgula ou um array com nos nomes das colunas)
    protected $tableColumns = '*';
    /// Relação de colunas calculadas pela classe
    protected $calculatedColumns = null;
    /// Colunas que determinam a chave primária
    protected $primaryKey = 'id';
    /// Nome da coluna que armazena a data de inclusão do registro (será utilizada pelo método save)
    protected $insertDateColumn = null;
    /// Nome da coluna usada para definir que o registro foi excluído
    protected $deletedColumn = null;
    /// Colunas passíveis de alteração
    protected $writableColumns = [];
    /// Colunas que precisam passar por algum método de alteração
    protected $hookedColumns = [];
    /// Colunas passíveis de ordenação para a busca
    protected $orderColumns = [];
    /// Propriedades do objeto
    protected $rows = [];
    /// Quantidade total de registros localizados no filtro
    protected $dbNumRows = 0;
    /// Flag de carga do banco de dados. Informa que os dados do objeto foram lidos do banco.
    protected $loaded = false;
    /// Container de mensagem de erros de validação.
    protected $validationErrors;
    /// Protege contra carga completa
    protected $abortOnEmptyFilter = true;
    /// Objetos relacionados
    protected $embeddedObj = [];
    /// Colunas para agrupamento de consultas
    protected $groupBy = [];
    /// Cláusula HAVING
    protected $having = [];
    /// The WHERE conditions
    public $where = null;

    /**
     *  \brief Método construtor da classe.
     *
     *  \param $filtro - Filto de busca, opcional. Deve ser um array de campos ou inteiro com ID do usuário.
     */
    public function __construct($filter = null, $database = 'default')
    {
        parent::__construct($database);

        $this->where = new Where();

        if (is_array($filter)) {
            $this->load($filter);
        }
    }

    /**
     *  \brief Embbed rows of other tables in each row.
     *  \see setEmbeddedObj().
     *
     *  Se o parâmetro $embbed for um inteiro maior que zero e o atributo embeddedObj estiver definido,
     *  o relacionamento definido pelo atributo será explorado até o enézimo nível definido por $embbed
     *
     *  \note ATENÇÃO: é recomendado cuidado com o valor de $embbed para evitar loops muito grandes ou estouro
     *      de memória, pois os objetos podem relacionar-se cruzadamente causando relacionamento reverso
     *      infinito.
     */
    private function _queryEmbbed($embbed)
    {
        if (is_int($embbed) && $embbed > 0 && count($this->embeddedObj) && count($this->rows) > 0) {
            foreach ($this->embeddedObj as $obj => $attr) {
                if (isset($attr['model'])) {
                    $attr['attr_name'] = (isset($attr['attr_name']) ? $attr['attr_name'] : $obj);
                }
                // Back compatibility to fix a bug
                if (!isset($attr['column'])) {
                    $attr['column'] = $attr['fk'];
                }
                if (!isset($attr['found_by'])) {
                    $attr['found_by'] = $attr['pk'];
                }
                if (!isset($attr['type'])) {
                    $attr['type'] = isset($attr['attr_type']) ? $attr['attr_type'] : 'list';
                }

                $keys = [];
                foreach ($this->rows as $idx => $row) {
                    $this->rows[$idx][$attr['attr_name']] = [];
                    if (!in_array($row[$attr['column']], $keys)) {
                        $keys[] = $row[$attr['column']];
                    }
                }

                // Filter
                if (isset($attr['filter']) && is_array($attr['filter'])) {
                    $efilter = array_merge(
                        [
                            $attr['found_by'] => ['in' => $keys],
                        ],
                        $attr['filter']
                    );
                } else {
                    $efilter = [
                        $attr['found_by'] => ['in' => $keys],
                    ];
                }
                // Order
                if (isset($attr['order'])) {
                    $order = $attr['order'];
                } else {
                    $order = [];
                }
                // Offset
                if (isset($attr['offset'])) {
                    $offset = $attr['offset'];
                } else {
                    $offset = null;
                }
                // Limit
                if (isset($attr['limit'])) {
                    $limit = $attr['limit'];
                } else {
                    $limit = null;
                }

                if (isset($attr['model'])) {
                    $embObj = new $attr['model']();
                } else {
                    $embObj = new $obj();
                }
                if (isset($attr['columns']) && is_array($attr['columns'])) {
                    $embObj->setColumns($attr['columns']);
                }
                if (isset($attr['group_by']) && is_array($attr['group_by'])) {
                    $embObj->groupBy($attr['group_by']);
                }
                if (isset($attr['embedded_obj'])) {
                    $embObj->setEmbeddedObj($attr['embedded_obj']);
                }
                $embObj->query($efilter, $order, $offset, $limit, $embbed - 1);
                while ($er = $embObj->next()) {
                    foreach ($this->rows as $idx => $row) {
                        if ($er[$attr['found_by']] == $row[$attr['column']]) {
                            if ($attr['type'] == 'list') {
                                $this->rows[$idx][$attr['attr_name']][] = $er;
                            } else {
                                $this->rows[$idx][$attr['attr_name']] = $er;
                            }
                        }
                    }
                }
                unset($embObj);
                reset($this->rows);
            }
        }
    }

    /**
     *  \brief Build a JOIN string with received array.
     *
     *  Monta os JOINs caso um array seja fornecido.
     *
     *  Cada item do array de JOINs deve ser um array, cujo índice representa o nome da tabela e contendo
     *  as seguintes chaves em seu interior: 'columns', 'join' e 'on'.
     *
     *  Cada chave do sub-array representa o seguinte:
     *      'join' determina o tipo de JOIN. Exemplos: 'INNER', 'LEFT OUTER'.
     *      'columns' define lista de campos, separada por vírgulas, a serem acrescidos ao SELECT.
     *          Recomenda-se preceder cada coluna com o nome da tabela para evitar ambiguidade.
     *      'on' é a cláusula ON para junção das tabelas.
     *
     *  Example of parameter $embbed as array to be used as JOIN:
     *
     *  [
     *      'table_name' => [
     *          'join'    => 'INNER',
     *          'on'      => 'table_name.id = fk_id',
     *          'columns' => 'table_name.column1 AS table_name_column1, table_name.column2',
     *      ],
     *  ]
     *
     *  or:
     *
     *  [
     *      [
     *          'join'    => 'INNER',
     *          'table'   => 'table_name',
     *          'on'      => 'table_name.id = fk_id',
     *          'columns' => 'table_name.column1 AS table_name_column1, table_name.column2',
     *      ],
     *  ]
     */
    private function _queryJoin($join, &$columns, &$from)
    {
        if (!is_array($join)) {
            return;
        }

        foreach ($join as $table => $meta) {
            if (!empty($meta['columns'])) {
                $columns .= ', '.$meta['columns'];
            } elseif (!empty($meta['fields'])) {
                $columns .= ', '.$meta['fields'];
            }
            if (!isset($meta['join'])) {
                if (!isset($meta['type'])) {
                    $meta['join'] = 'INNER';
                } else {
                    $meta['join'] = $meta['type'];
                }
            }
            $from .= ' '.$meta['join'].' JOIN '.(isset($meta['table']) ? $meta['table'] : $table).' ON '.$meta['on'];
        }
    }

    /**
     *  \brief Verifica se a chave primária está definida.
     *
     *  \return Retorna TRUE se todas as colunas da chave primária estão definiadas e FALSE em caso contrário.
     */
    protected function isPrimaryKeyDefined()
    {
        if (empty($this->primaryKey)) {
            return false;
        }

        $primary = $this->getPKColumns();
        foreach ($primary as $column) {
            if (!isset($this->rows[key($this->rows)][$column])) {
                return false;
            }
        }

        return true;
    }

    /**
     * \brief Retorna as configurações de regras para validação dos dados do model.
     *
     * \note Este método deve ser extendido na classe herdeira
     *
     * \return array
     */
    protected function validationRules()
    {
        return [];
    }

    /**
     * \brief Mensagens de erros customizadas para cada tipo de validação à ser
     *        realizado neste model.
     *
     * \note Este método deve ser extendido na classe herdeira
     *
     * \return array
     */
    protected function validationErrorMessages()
    {
        return [];
    }

    /**
     *  \brief Gatilho que será executado antes de um DELETE.
     *
     *  Esse método existe para ser estendido, opcionalmente, na classe herdeira
     *  caso algum tratamento precise ser feito antes da exclusão de um registro
     */
    protected function triggerBeforeDelete()
    {
        return true;
    }

    /**
     *  \brief Gatilho que será executado antes de um INSERT.
     *
     *  Esse método existe para ser estendido, opcionalmente, na classe herdeira
     *  caso algum tratamento precise ser feito antes da enclusão de um registro
     */
    protected function triggerBeforeInsert()
    {
        return true;
    }

    /**
     *  \brief Gatilho que será executado antes de um UPDATE.
     *
     *  Esse método existe para ser estendido, opcionalmente, na classe herdeira
     *  caso algum tratamento precise ser feito antes da alteração de um registro
     */
    protected function triggerBeforeUpdate()
    {
        return true;
    }

    /**
     *  \brief Gatilho que será executado depois de um DELETE.
     *
     *  Esse método existe para ser estendido, opcionalmente, na classe herdeira
     *  caso algum tratamento precise ser feito depois da exclusão de um registro
     */
    protected function triggerAfterDelete()
    {
        return true;
    }

    /**
     *  \brief Gatilho que será executado depois de um INSERT.
     *
     *  Esse método existe para ser estendido, opcionalmente, na classe herdeira
     *  caso algum tratamento precise ser feito depois da inclusão de um registro
     */
    protected function triggerAfterInsert()
    {
        return true;
    }

    /**
     *  \brief Gatilho que será executado depois de um UPDATE.
     *
     *  Esse método existe para ser estendido, opcionalmente, na classe herdeira
     *  caso algum tratamento precise ser feito depois da alteração de um registro
     */
    protected function triggerAfterUpdate()
    {
        return true;
    }

    /**
     *  \brief Retorna um array com a(s) coluna(s) da chave primária.
     */
    public function getPKColumns()
    {
        if (empty($this->primaryKey)) {
            return false;
        } elseif (is_array($this->primaryKey)) {
            $tpk = $this->primaryKey;
        } else {
            $tpk = explode(',', $this->primaryKey);
        }

        return $tpk;
    }

    /**
     * \brief Retorna o container de mensagens de errors que guardará as mensagens de erros
     *       vindas do teste de validação.
     *
     * \return Springy\Utils\MessageContainer
     */
    public function validationErrors()
    {
        return $this->validationErrors;
    }

    /**
     * \brief Realiza uma validação dos dados populados no objeto, passando-os por testes
     *        de acordo com  as configurações regras estipulados no método 'validationRules()'.
     *
     * \return bool resultado da validação, true para passou e false para não passou
     */
    public function validate()
    {
        if (!$this->valid()) {
            return false;
        }

        $data = $this->current();

        $validation = Validator::make(
            $data,
            $this->validationRules(),
            $this->validationErrorMessages()
        );

        $result = $validation->validate();

        $this->validationErrors = $validation->errors();

        return $result;
    }

    /**
     *  \brief Método de carga do objeto.
     *
     *  Busca um registro específico e o carrega para as propriedades.
     *
     *  Caso mais de um registro seja localizado, descarta a busca e considera com não carregado.
     *
     *  \return Retorna TRUE se encontrar um registro que se adeque aos filtros de busca. Retorna FALSE em caso contrário.
     */
    public function load($filter = null)
    {
        if ($this->query($filter) && $this->dbNumRows == 1) {
            $this->loaded = true;
        } else {
            $this->rows = [];
        }

        return $this->dbNumRows == 1;
    }

    /**
     *  \brief Retorna um array das colunas alteradas no registro corrente.
     */
    public function changedColumns()
    {
        if ($this->valid()) {
            return isset($this->rows[key($this->rows)]['**CHANGED**']) ? $this->rows[key($this->rows)]['**CHANGED**'] : [];
        }

        return [];
    }

    /**
     *  \brief Limpa a relação de colunas alteradas.
     */
    public function clearChangedColumns()
    {
        if ($this->valid()) {
            $this->rows[key($this->rows)]['**CHANGED**'] = [];
        }
    }

    /**
     *  \brief Informa se o registro foi carregado com dados do banco.
     */
    public function isLoaded()
    {
        return $this->loaded;
    }

    /**
     *  \brief Send the changes in current row to the database.
     *
     *  \return Retorna TRUE se o dado foi salvo ou FALSE caso nenhum dado tenha sido alterado.
     */
    public function save($onlyIfValidationPasses = true)
    {
        // Se o parametro de salvar o objeto somente se a validação passar,
        // então é feita a validação e se o teste falar, retorna falso sem salvar
        if ($onlyIfValidationPasses && !$this->validate()) {
            try {
                // Se houver erros e o objeto de flashdata estiver registrado
                // salvá-los nos dados de flash para estarem disponívels na variavel
                // de template global '$errors' somente durante o próximo request.
                app('session.flashdata')->setErrors($this->validationErrors());
            } catch (Exception $e) {
            }

            return false;
        }

        // If there is no change, do nothing.
        if (!isset($this->rows[key($this->rows)]['**CHANGED**']) || count($this->rows[key($this->rows)]['**CHANGED**']) < 1) {
            return false;
        }

        // Build the list of columns was changed.
        $columns = [];
        $values = [];
        foreach ($this->rows[key($this->rows)]['**CHANGED**'] as $column) {
            $columns[] = $column;
            $values[] = $this->rows[key($this->rows)][$column];
        }

        if (!isset($this->rows[key($this->rows)]['**NEW**'])) {
            /*
             *  Is not a new record. Then update current.
             */

            // There is no primary key to build condition, do nothing.
            if (!$this->isPrimaryKeyDefined()) {
                return false;
            }

            // Call before update trigger
            if (!$this->triggerBeforeUpdate()) {
                return false;
            }

            $where = new Where();
            foreach ($this->getPKColumns() as $column) {
                $where->condition($column, $this->rows[key($this->rows)][$column]);
            }
            $this->execute('UPDATE '.$this->tableName.' SET '.implode(' = ?,', $columns).' = ?'.$where, array_merge($values, $where->params()));

            // Call after update trigger
            $this->triggerAfterUpdate();
        } else {
            /*
             *  Is a new record.
             */

            // Call before insert trigger
            if (!$this->triggerBeforeInsert()) {
                return false;
            }

            // Database function to populate created at column
            switch ($this->driverName()) {
                case 'oci':
                case 'oracle':
                case 'mysql':
                case 'pgsql':
                    $cdtFunc = 'NOW()';
                    break;
                case 'mssql':
                case 'sqlsrv':
                    $cdtFunc = 'GETDATE()';
                    break;
                case 'db2':
                case 'ibm':
                case 'ibm-db2':
                case 'firebird':
                    $cdtFunc = 'CURRENT_TIMESTAMP';
                    break;
                case 'informix':
                    $cdtFunc = 'CURRENT';
                    break;
                case 'sqlite':
                    $cdtFunc = 'datetime(\'now\')';
                    break;
                default:
                    $cdtFunc = '\''.date('Y-m-d H:i:s').'\'';
            }

            $this->execute('INSERT INTO '.$this->tableName.' ('.implode(', ', $columns).($this->insertDateColumn ? ', '.$this->insertDateColumn : '').') VALUES ('.rtrim(str_repeat('?,', count($values)), ',').($this->insertDateColumn ? ', '.$cdtFunc : '').')', $values);
            // Load the insertd row
            if ($this->affectedRows() == 1) {
                if ($this->lastInsertedId() && !empty($this->primaryKey) && !strpos($this->primaryKey, ',') && empty($this->rows[key($this->rows)][$this->primaryKey])) {
                    $this->load([$this->primaryKey => $this->lastInsertedId()]);
                } elseif ($this->isPrimaryKeyDefined()) {
                    $filter = [];
                    foreach ($this->getPKColumns() as $col) {
                        $filter[$col] = $this->rows[key($this->rows)][$col];
                    }
                    $this->load($filter);
                    unset($filter);
                }
            }

            // Call after insert trigger
            $this->triggerAfterInsert();
        }

        $this->clearChangedColumns();

        return $this->affectedRows() > 0;
    }

    /**
     *  \brief Delete one or more rows.
     *
     *  This method deletes the curret row or many rows if the $filter is given.
     *
     *  \param $filter is an array or Where object with a match criteria.
     *      If ommited (null) deletes the current row selected.
     *  \return Returns the number of affected rows or false.
     */
    public function delete($filter = null)
    {
        if (!is_null($filter) || $this->where->count()) {
            /*
             *  Delete rows with a filter.
             *
             *  In this method triggers will not be called.
             */

            // Build the condition
            if (is_array($filter)) {
                $where = new Where();
                $where->filter($filter);
            } elseif ($filter instanceof Where) {
                $where = clone $filter;
            } else {
                $where = clone $this->where;
            }

            if (!empty($this->deletedColumn)) {
                // If table has a deleted column flag, update the rows
                $where->condition($this->deletedColumn, 0);
                $this->execute('UPDATE '.$this->tableName.' SET '.$this->deletedColumn.' = 1'.$where, $where->params());
            } else {
                // Otherwise delete the row
                $this->execute('DELETE FROM '.$this->tableName.$where, $where->params());
            }

            // Clear any conditions
            $this->where->clear();
        } elseif ($this->valid()) {
            /*
             *  Delete de current row.
             */

            // Do nothing if there is no primary key defined
            if (!$this->isPrimaryKeyDefined()) {
                return false;
            }

            // Build the primary key to define the row to be deleted
            $where = new Where();
            foreach (explode(',', $this->primaryKey) as $column) {
                $where->condition($column, $this->get($column));
            }

            // Call before delete trigger
            if (!$this->triggerBeforeDelete()) {
                return false;
            }

            if (!empty($this->deletedColumn)) {
                // If table has a deleted column flag, update the row
                $where->condition($this->deletedColumn, 0);
                $this->execute('UPDATE '.$this->tableName.' SET '.$this->deletedColumn.' = 1'.$where, $where->params());
            } else {
                // Otherwise delete the row
                $this->execute('DELETE FROM '.$this->tableName.$where, $where->params());
            }
            // Call after delete trigger
            $this->triggerAfterDelete();
        } else {
            return false;
        }

        // Clear conditions avoid bug
        $this->where->clear();

        return $this->affectedRows();
    }

    /**
     *  \brief Faz alteração em lote.
     *
     *  EXPERIMENTAL!
     *
     *  Permite fazer atualização de registros em lote (UPDATE)
     */
    public function update(array $values, $conditions = null)
    {
        if (is_null($conditions)) {
            return false;
        }

        $data = [];
        $params = [];

        foreach ($values as $column => $value) {
            if (in_array($column, $this->writableColumns)) {
                if (is_callable($value)) {
                    if (isset($this->hookedColumns[$column]) && method_exists($this, $this->hookedColumns[$column])) {
                        $data[] = $column.' = '.call_user_func_array([$this, $this->hookedColumns[$column]], [$value()]);
                    } else {
                        $data[] = $column.' = '.$value();
                    }
                } else {
                    $data[] = $column.' = ?';
                    if (isset($this->hookedColumns[$column]) && method_exists($this, $this->hookedColumns[$column])) {
                        $params[] = call_user_func_array([$this, $this->hookedColumns[$column]], [$value]);
                    } else {
                        $params[] = $value;
                    }
                }
            }
        }

        // The WHERE clause
        if ($conditions instanceof Where) {
            $where = clone $conditions;
        } elseif (is_array($conditions)) {
            $where = new Where();
            $where->filter($conditions);
        } else {
            throw new \Exception('Invalid condition type.', 500);
        }

        if (!empty($this->deletedColumn) && !$where->get($this->deletedColumn)) {
            $where->condition($this->deletedColumn, 0);
        }
        $this->execute('UPDATE '.$this->tableName.' SET '.implode(', ', $data).$where, array_merge($params, $where->params()));

        // Clear conditions avoid bug
        $this->where->clear();

        return $this->affectedRows();
    }

    /**
     *  \brief Pega uma coluna ou um registro dos atributos de dados.
     *
     *  \param (string)$column - Nome da coluna desejada ou null caso queira o array contendo a linha atual
     *
     *  \return Retorna o conteúdo da coluna passada, um array com as colunas do registro atual ou NULL
     */
    public function get($column = null)
    {
        if (is_null($column)) {
            return current($this->rows);
        }

        $columns = current($this->rows);
        if (isset($columns[$column])) {
            return $columns[$column];
        }
    }

    /**
     *  \brief Altera o valor de uma coluna.
     *
     *  \return Retorna TRUE se alterou o valor da coluna ou FALSE caso a coluna não exista ou não haja registro carregado
     */
    public function set($column, $value = null)
    {
        if (is_array($column)) {
            foreach ($column as $key => $val) {
                $this->set($key, $val);
            }

            return true;
        }

        if (in_array($column, $this->writableColumns)) {
            if (empty($this->rows)) {
                $this->rows[] = ['**NEW**' => true];
            }

            $oldvalue = isset($this->rows[key($this->rows)][$column]) ? $this->rows[key($this->rows)][$column] : null;
            if (isset($this->hookedColumns[$column]) && method_exists($this, $this->hookedColumns[$column])) {
                $this->rows[key($this->rows)][$column] = call_user_func_array([$this, $this->hookedColumns[$column]], [$value]);
            } else {
                $this->rows[key($this->rows)][$column] = $value;
            }

            if ($oldvalue != $value || isset($this->rows[key($this->rows)]['**NEW**'])) {
                if (!isset($this->rows[key($this->rows)]['**CHANGED**'])) {
                    $this->rows[key($this->rows)]['**CHANGED**'] = [];
                }

                if (!in_array($column, $this->rows[key($this->rows)]['**CHANGED**'])) {
                    $this->rows[key($this->rows)]['**CHANGED**'][] = $column;
                }
            }

            return true;
        }

        return false;
    }

    /**
     *  \brief Define a relação de colunas para consultas.
     *
     *  Este método permite alterar a relação padrão de colunas a serem listadas em consultas com o método query
     *
     *  \params (array)$columns - array contendo a relação de colunas para o comando SELECT
     */
    public function setColumns(array $columns)
    {
        $cols = [];
        foreach ($columns as $column) {
            if (!strpos($column, '.') && !strpos($column, '(')) {
                $column = $this->tableName.'.'.$column;
            }
            $cols[] = $column;
        }

        $this->tableColumns = implode(',', $cols);
    }

    /**
     *  \brief Define o array de objetos embutidos.
     *
     *  O array de objetos embutidos é uma estrutura que permite a consulta a execução de consultas em outros objetos e embutir
     *  seu resultado dentro de um atributo do registro.
     *
     *  O índice de cada item do array de objetos embutidos será inserido no registro como uma coluna que pode ser um array de
     *  registros ou a estrutura de dados da Model embutida.
     *
     *  O valor de cada item do array deve ser um array com a seguinte extrutura:
     *
     *  'model' => (string) nome do atributo a ser criado no registro
     *  'type' => (constant)'list'|'data' determina como o atributo deve ser.
     *      - 'list' (default) define que o atributo é uma lista (array) de registros;
     *      - 'data' define que o atributo é um único registro do objeto embutido (array de colunas).
     *  'found_by' => (string) nome da coluna do objeto embutido que será usada como chave de busca.
     *  'column' => (string) nome da coluna que será usada para relacionamento com o objeto embutido.
     *  'columns' => (array) um array de colunas, opcional, a serem aplicados ao objeto embutido, no mesmo formato usados no método setColumns.
     *  'filter' => (array) um array de filtros, opcional, a serem aplicados ao objeto embutido, no mesmo formato usados no método query.
     *  'group_by' => (array) um array de agrupamento, opcional, a serem aplicados ao objeto embutido, no mesmo formato usados no método groupBy.
     *  'order' => (array) um array de ordenação, opcional, a ser aplicado ao objeto embutido, no mesmo formato usados no método query.
     *  'offset' => (int) o offset de registros, opcional, a ser aplicado ao objeto embutido, no mesmo formato usados no método query.
     *  'limit' => (int) o limite de registros, opcional, a ser aplicado ao objeto embutido, no mesmo formato usados no método query.
     *  'embbeded_obj' => (array) um array estrutura, opcional, para embutir outro objeto no objeto embutido.
     *
     *  Exemplo de array aceito:
     *
     *  array('parent' => array('model' => 'Parent_Table', 'type' => 'data', 'found_by' => 'id', 'column' => 'parent_id'))
     */
    public function setEmbeddedObj(array $embeddedObj)
    {
        $this->embeddedObj = $embeddedObj;
    }

    /**
     *  \brief Define colunas para agrupamento do resultado.
     *
     *  Este método permite definir a relação de colunas para a cláusula GROUP BY da consulta com o método query
     *
     *  \params (array)$columns - array contendo a relação de colunas para a cláusula GROUP BY
     *  \note ESTE MÉTODO AINDA É EXPERIMENTAL
     */
    public function groupBy(array $columns)
    {
        $cols = [];
        foreach ($columns as $column) {
            if (!strpos($column, '.') && !strpos($column, '(')) {
                $column = $this->tableName.'.'.$column;
            }
            $cols[] = $column;
        }

        $this->groupBy = $cols;
    }

    /**
     *  \brief Define atributos para a cláusula HAVING.
     *
     *  Este método permite definir a cláusula HAVING para agrupamento
     *
     *  \params (array)$columns - array contendo a relação de colunas para a cláusula GROUP BY
     *  \note ESTE MÉTODO AINDA É EXPERIMENTAL
     */
    public function having(array $conditions)
    {
        $this->having = $conditions;
    }

    /**
     *  \brief Método de consulta ao banco de dados.
     *
     *  \param (array)$filter - array contendo o filtro de registros no formato 'coluna' => valor
     *  \param (array)$orderby - array contendo o filtro de registros no formato 'coluna' => 'ASC'/'DESC'
     *  \param (int)$offset - inteiro que define o offset de registros
     *  \param (int)$limit - inteiro que define o limite de registros a serem retornados
     *  \param (variant)$embbed - esse parâmetro pode ser um array contendo uma estrutura para montagem
     *    de cláusulas JOIN para a query ou um inteiro. Se omitido, nada será feito com ele.
     *    Se receber um valor inteiro, fará com que a pesquisa utilize o atributo $this->embeddedObj
     *    para alimentar as linhas com os dados dos objetos relacionados até a o nível definido por seu valor.
     *
     *  \return Retorna TRUE caso tenha efetuado a busca ou FALSE caso não tenha recebido filtros válidos.
     *  \note Mesmo que o método retorne TRUE, não significa que algum dado tenha sido encontrado.
     *    Isso representa apenas que a consulta foi efetuado com sucesso. Para saber o resultado da
     *    consulta, utilize os métodos de recuperação de dados.
     */
    public function query($filter = null, array $orderby = [], $offset = 0, $limit = 0, $embbed = false)
    {
        // Monta o conjunto de colunas da busca
        if (is_array($this->tableColumns)) {
            $columns = $this->tableName.'.'.implode(', '.$this->tableName.'.', $this->tableColumns);
        } else {
            $columns = (!strpos($this->tableColumns, '.') && !strpos($this->tableColumns, '(')) ? $this->tableName.'.'.$this->tableColumns : $this->tableColumns;
        }

        $select = 'SELECT '.($this->driverName() == 'mysql' ? 'SQL_CALC_FOUND_ROWS ' : '').$columns;
        $from = ' FROM '.$this->tableName;
        unset($columns);

        // Build the conditions (if has)
        if (is_array($filter)) {
            // Use filter as array (legacy)
            $where = new Where();
            $where->filter($filter);
        } elseif ($filter instanceof Where) {
            // Filter is a new Where object
            $where = clone $filter;
        } else {
            $where = clone $this->where;
        }

        // Abandona caso não hajam filtros
        if ($this->abortOnEmptyFilter && !$where->count()) {
            return false;
        }

        // Se há uma coluna de exclusão lógica definida, adiciona-a ao conjunto de filtros
        if ($this->deletedColumn && !$where->get($this->deletedColumn) && !$where->get($this->tableName.'.'.$this->deletedColumn)) {
            $where->condition($this->tableName.'.'.$this->deletedColumn, 0);
        }

        $this->_queryJoin($embbed, $select, $from); // Table joins?

        $sql = $select.$from.$where.(!empty($this->groupBy) ? ' GROUP BY '.implode(', ', $this->groupBy) : '');
        $params = $where->params();

        // Monta a cláusula HAVING de condicionamento
        if (!empty($this->having)) {
            $conditions = new Conditions();
            $conditions->filter($this->having);
            $sql .= ' HAVING '.$conditions;
            $params = array_merge($params, $conditions->params());
            unset($conditions);
        }

        // Monta a ordenação do resultado de busca
        if (!empty($orderby)) {
            $order = [];
            foreach ($orderby as $column => $direction) {
                $order[] = "$column $direction";
            }

            if (!empty($order)) {
                $sql .= ' ORDER BY '.implode(', ', $order);
            }
        }

        // Monta o limitador de registros
        if ($limit > 0) {
            $sql .= ' LIMIT '.$offset.', '.$limit;
        }

        // Limpa as propriedades da classe
        $this->loaded = false;

        // Efetua a busca
        $this->execute($sql, $params);
        $this->rows = $this->fetchAll();
        unset($sql);

        // Faz a contagem de registros do filtro apenas se foi definido um limitador de resultador
        if ($limit > 0) {
            if ($this->driverName() == 'mysql') {
                $this->execute('SELECT FOUND_ROWS() AS found_rows');
            } else {
                $sql = 'SELECT COUNT(0) AS found_rows FROM '.$this->tableName.$where;

                $this->execute($sql, $where->params());
            }
            $columns = $this->fetchNext();
            $this->dbNumRows = (int) $columns['found_rows'];
        } else {
            $this->dbNumRows = count($this->rows);
        }
        unset($where, $params);

        $this->_queryEmbbed($embbed);

        // Populate de calculated columns
        if (is_array($this->calculatedColumns) && count($this->calculatedColumns)) {
            foreach ($this->rows as $idx => $row) {
                foreach ($this->calculatedColumns as $column => $method) {
                    if (method_exists($this, $method)) {
                        $this->rows[$idx][$column] = $this->$method($row);
                    } else {
                        $this->rows[$idx][$column] = null;
                    }
                }
            }
            reset($this->rows);
        }

        // Clear conditions avoid bug
        $this->where->clear();

        return true;
    }

    /**
     *  \brief Todos os registros.
     *
     *  \return Retorna um array com todas as linhas do resultset
     */
    public function all()
    {
        return $this->rows;
    }

    /**
     *  \brief Move o ponteiro para o primeiro registro e retorna o registro.
     *
     *  \return Retorna o primeiro registro ou FALSE caso não haja registros.
     */
    public function reset()
    {
        return reset($this->rows);
    }

    /**
     *  \brief Move o ponteiro para o registro anteior e retorna o registro.
     *
     *  \return Retorna o registro anterior ou FALSE caso não haja mais registros.
     */
    public function prev()
    {
        return prev($this->rows);
    }

    /**
     *  \brief Retorna o registro corrente e move o ponteiro para o próximo registro.
     *
     *  \return Retorna o próximo registro da fila ou FALSE caso não haja mais registros.
     */
    public function next()
    {
        if ($r = each($this->rows)) {
            return $r['value'];
        }

        return false;
    }

    /**
     *  \brief Move o ponteiro para o último registro e retorna o registro.
     *
     *  \return Retorna o último registro ou FALSE caso não haja registros.
     */
    public function end()
    {
        return end($this->rows);
    }

    /**
     *  \brief Retorna todos os dados de uma determinada coluna.
     *
     *  \return Retorna um array de valores de uma determinada coluna do resultset.
     */
    public function getAllColumn($column)
    {
        return array_column($this->rows, $column);
    }

    /**
     *  \brief Quantidade de linhas encontradas para uma determinada condição.
     *
     *  \return Retorna a quantidade de registros encontrados para uma determinada condição
     */
    public function count($filter = null, $embbed = false)
    {
        $select = 'SELECT COUNT(0) AS rowscount';
        $from = ' FROM '.$this->tableName;

        // Monta o conjunto de filtros personalizado da classe herdeira
        if (is_array($filter)) {
            $where = new Where();
            $where->filter($filter);
        } elseif ($filter instanceof Where) {
            // Filter is a new Where object
            $where = clone $filter;
        } else {
            $where = clone $this->where;
        }

        // Se há uma coluna de exclusão lógica definida, adiciona-a ao conjunto de filtros
        if ($this->deletedColumn) {
            if (isset($filter[$this->deletedColumn])) {
                $this->where->condition($this->deletedColumn, (int) $filter[$this->deletedColumn]);
            } else {
                $this->where->condition($this->deletedColumn, 0);
            }
        }

        $this->_queryJoin($embbed, $select, $from); // Table joins?

        // Se há uma coluna de exclusão lógica definida, adiciona-a ao conjunto de filtros
        if ($this->deletedColumn && !$where->get($this->deletedColumn) && !$where->get($this->tableName.'.'.$this->deletedColumn)) {
            $where->condition($this->tableName.'.'.$this->deletedColumn, 0);
        }

        $sql = $select.$from.$where;

        // Executa o comando de contagem
        $this->execute($sql, $where->params());
        $row = $this->fetchNext();

        return (int) $row['rowscount'];
    }

    /**
     *  \brief Quantidade de linhas do resultset.
     *
     *  \return Retorna a quantidade de registros contidos no resultset da última consulta
     */
    public function rows()
    {
        return count($this->rows);
    }

    /**
     *  \brief Dá o número de linhas encotnradas no banco de dados.
     *
     *  \return Retorna a quantidade de registros encntrados no banco de dados para a última busca efetuada.
     */
    public function foundRows()
    {
        return $this->dbNumRows;
    }

    /**
     *  \brief Alias de get(), para retornar columns como se fossem propriedades
     *  \param variant $name
     *  \return variant.
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     *  \brief Alias de set(), para setar columns como se fossem propriedades
     *  \param string $name
     *  \param variant $value.
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     *  \brief Retorna o registro atual.
     */
    public function current()
    {
        return current($this->rows);
    }

    /**
     *  \brief Retorna os nomes das colunas.
     */
    public function key()
    {
        return key($this->rows);
    }

    /**
     *  \brief Alias para reset
     *  \see reset.
     */
    public function rewind()
    {
        $this->reset();
    }

    /**
     *  \brief Verifica se o registro atual existe.
     */
    public function valid()
    {
        return $this->current() !== false;
    }
}
