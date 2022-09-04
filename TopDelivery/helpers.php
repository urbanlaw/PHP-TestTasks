<?php

define('DBSERVER', DB_SERVER);
define('DBLOGIN', DB_USERNAME);
define('DBPSWD', DB_PASSWORD);
define('DBDATABASE', 'test');

class DBH
{
    private mysqli $db;

    public static function Factory()
    {
        return new static(DBSERVER, DBLOGIN, DBPSWD, DBDATABASE);
    }

    public function __construct(string $host, string $user, string $pswd, string $db)
    {
        $this->db = new mysqli($host, $user, $pswd, 'test');
        if($this->db->connect_errno)
        {
            throw new Exception('Unable to connect to DB');
        }
    }

    public function QueryArr($query)
    {
        return $this->Query($query)->fetch_all(MYSQLI_ASSOC);
    }

    public function QueryRow($query)
    {
        return $this->Query($query)->fetch_assoc();
    }

    public function Query($query)
    {
        if($result = $this->db->query($query))
        {
            return $result;
        }
        else
        {
            throw new Exception('DB error: '.$this->db->error);
        }
    }

    public function Esc($value)
    {
        return $this->db->real_escape_string($value);
    }
}

class CsvParser
{
    private string $fieldDelimiter;
    private string $lineDelimiter;

    private array $headers = [];
    private array $dataset = [];

    public function __construct(string $fieldDelimiter = ';', string $lineDelimiter = "\n")
    {
        $this->fieldDelimiter = $fieldDelimiter;
        $this->lineDelimiter = $lineDelimiter;
    }

    public function Dataset()
    {
        return $this->dataset;
    }

    public function SetDataset($dataset)
    {
        $this->dataset = $dataset;
        return $this;
    }

    public function Headers()
    {
        return $this->headers;
    }

    public function SetHeaders($headers)
    {
        $this->headers = $headers;
        return $this;
    }

    public function Read($path)
    {
        $content = file_get_contents($path);
        if($content === false)
        {
            throw new Exception('Unable to read file: ' . $path);
        }
        $content = trim($content);
        $csvLines = explode($this->lineDelimiter, $content);

        $this->dataset = array_map(function($el) {
            return explode($this->fieldDelimiter, $el);
        }, $csvLines);

        $this->headers = array_shift($this->dataset);
        return $this;
    }

    public function Write($path)
    {
        $arr = $this->dataset;
        array_unshift($arr, $this->headers);

        $lines = array_map(function($el) { return implode($this->fieldDelimiter, $el); }, $arr);
        $content = implode($this->lineDelimiter, $lines);

        if(!file_put_contents($path, $content))
        {
            throw new Exception('Unable to write file: ' . $path);
        }
    }
}
