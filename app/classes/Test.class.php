<?php

use Springy\Model;

class Test extends Model
{
    /// The table name
    protected $tableName = 'tests';
    /// The primary key column
    protected $primaryKey = 'id';
    /// The name of the column where the insert datetime is saved
    protected $insertDateColumn = 'created_at';
    /// The name of the column to set a logic exclusion
    protected $deletedColumn = 'deleted';
    /// Columns who can be updated by application
    protected $writableColumns = ['name'];

    public function parseData(array $data)
    {
        foreach($data as $key => $value)
        {
            if (in_array($key, $this->writableColumns)) {
                $this->$key = $value;
            }
        }
    }
}