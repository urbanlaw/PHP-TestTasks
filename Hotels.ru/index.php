<?php

MultiplicationTable::Factory(33)->Render();
echo '<br/>';

DeclensionMaker::Debug();
echo '<br/>';

/** ТЗ - Вывести таблицу умножения, красивенькую */
class MultiplicationTable
{
    private const MIN = 1;

    private array $matrix = [];
    private int $max;

    public static function Factory(int $max): self
    {
        return new static($max);
    }

    public function __construct(int $max)
    {
        $this->max = $max;

        for($y = self::MIN; $y <= $max; $y++)
        {
            for($x = self::MIN; $x <= $max; $x++)
            {
                $this->matrix[$y][$x] = $x * $y;
            }
        }
    }

    /** Выводит таблицу умножения */
    public function Render()
    {
        $leftIndexSize = strlen($this->max) + 2;
        $colSizes = [];
        foreach(end($this->matrix) as $x => $value)
        {
            $colSizes[$x] = strlen($value) + 1;
        }

        echo '<pre>';

        $this->RenderTopIndex($leftIndexSize, $colSizes);
        foreach($this->matrix as $y => $row)
        {
            echo str_pad($y . ' |', $leftIndexSize, ' ', STR_PAD_LEFT);
            $this->RenderRow($row, $colSizes);
        }

        echo '</pre>';
    }

    /** Выводит заголовок таблицы
     * @param int $leftIndexSize - ширина колонки индексов
     * @param array $colSizes - массив ширины колонок
     */
    private function RenderTopIndex(int $leftIndexSize, array $colSizes)
    {
        echo str_pad('', $leftIndexSize);
        for($x = self::MIN; $x <= $this->max; $x++)
        {
            echo str_pad($x, $colSizes[$x], ' ', STR_PAD_LEFT);
        }
        echo "\n";

        echo str_pad('', $leftIndexSize);
        for($x = self::MIN; $x <= $this->max; $x++)
        {
            echo str_pad('', $colSizes[$x], '-', STR_PAD_LEFT);
        }
        echo "\n";
    }

    /** Выводит строку матрицы
     * @param array $row
     * @param array $colSizes
     */
    private function RenderRow(array $row, array $colSizes)
    {
        foreach($row as $x => $value)
        {
            $colSize = $colSizes[$x];
            echo str_pad($value, $colSize, ' ', STR_PAD_LEFT);
        }
        echo "\n";
    }
}

/** ТЗ - Вернуть слово "компьютер" в нужном падеже в зависимости от количестваа */
class DeclensionMaker
{
    /** Возвращает слово "компьютер" в падеже соответствующем количеству
     * @param int $qty - количество
     * @return string
     */
    public static function MakeComputer(int $qty): string
    {
        return static::Make('компьютер', $qty);
    }

    /** Возвращает слово в падеже соответствующем количеству, поддерживает только мужской род
     * @param string $word - слово в мужском роде
     * @param int $qty - количество
     * @return string
     */
    private static function Make(string $word, int $qty): string
    {
        if($word == '')
        {
            throw new InvalidArgumentException('Word should not be empty');
        }

        $suffix = 'ов';

        if(strlen($qty) == 1 || substr($qty, -2, 1) != '1')
        {
            $lastNum = substr((string)$qty, -1);

            if($lastNum == 1)
            {
                $suffix = '';
            }
            else if($lastNum >= 2 && $lastNum <= 4)
            {
                $suffix = 'а';
            }
        }

        return $word . $suffix;
    }

    public static function Debug()
    {
        for($qty = 0; $qty <= 33; $qty++)
        {
            echo $qty . ' ' . DeclensionMaker::MakeComputer($qty) . '<br/>';
        }
    }
}
