<?php
    function read_data() {
        $data = explode(';', $_POST['groups']); // Считали данные и разбили через ';'
        foreach ($data as &$line) {
            $line = explode(', ', $line); // Разбили данные внутри строки черех ' '
        }

        return $data;
    }

    // Мне пришлось использовать это дикое решение из-за проблем с кодировкой.
    // Латиница занимает одну ячейку в памяти, а кириллица - две.
    function is_russian(string $char) {
        $alph = 'АаБбВвГгДдЕеЁёЖжЗзИиЙйКкЛлМмНнОоПпРрСсТтУуФфХхЦцЧчШшЩщЪъЫыЬьЭэЮюЯя';
        $i = 0;
        while ($i < strlen($alph)) {
            if ($char == $alph[$i]) {
                return true;
            }
            $i += 1;
        }

        return false;
    }

    function fix_combination(string &$combo) {
        $re_all = '/[a-zA-Z]|\d|\s/';
        $re_first = '/[!+-]|[a-zA-Z]|\d/';

        $combo = explode(' ', $combo);
        $buf = '';

        foreach ($combo as &$word) {
            $i = 0;
            while ($i < strlen($word)) {
                if ($i == 0) {
                    $isMatched = (preg_match($re_first, $word[$i]) or is_russian($word[$i]));
                }
                else {
                    $isMatched = (preg_match($re_all, $word[$i]) or is_russian($word[$i]));
                }
                
                if (!$isMatched) {
                    $word[$i] = ' ';
                }
                $i += 1;
            }
            if (strlen($word) <= 2) {
                $word = '+' . $word;
            }

            $buf .= $word . ' ';
        }
        $combo = $buf;
    }

    function fix_errors(array &$data) {
        foreach ($data as &$line) {
            foreach ($line as &$combination) {
                fix_combination($combination);
                }
            }

        return $data;
    }

    // Убирает пересечение ключевых слов
    function prep_data(array &$data) {
        $models = $data[0];

        foreach ($models as &$line) {
            $line = explode(' ', $line);
            array_pop($line); // Удаляем пустые ячейки с конца
        }

        $i = 0;
        while ($i < count($models) - 1) {

            $j = $i + 1;
            while ($j < count($models)) {

                $next = 0;
                while ($next < count($models[$j])){

                    $cur = 0;
                    $el_in = false;
                    while ($cur < count($models[$i])) {
                        if (($models[$i][$cur] == $models[$j][$next]) or ($models[$i][$cur] == '-' . $models[$j][$next])){
                            $el_in = true;
                        }
                        $cur += 1;
                    }
                    if (!$el_in) {
                        $models[$i][] = '-' . $models[$j][$next];
                    }
                    $next += 1;
                }
                $j += 1;
            }
            $i += 1;
        }

        foreach ($models as &$line) {
            $buf = '';

            foreach ($line as &$el) {
                $buf .= $el . ' ';
            }

            $line = $buf;
        }

        $data[0] = $models;
        return $data;
    }
    
    // Функция для отладки
    function print_array(array &$data, string $sep='') {
        foreach ($data as &$element) {
            if (is_array($element)) {
                print_array($element, $sep);
            }
            else {
                echo($element . $sep);
            }
        }
    }

    // Вспомогательная функция для генерации всех наборов
    function union(array &$a, array &$b) {
        $res = [];

        $i = 0;
        foreach ($a as $el_a) {
            $res[] = [];
            foreach ($b as $el_b) {
                $res[$i][] = $el_a;
                foreach ($el_b as &$kw) {
                    $res[$i][] = $kw;
                }
                $i += 1;
            }
        }

        return $res;
    }

    // Генерирует наборы
    function gen_kw(array &$data) {
        $i = count($data) - 1;

        $res = [];
        foreach ($data[$i] as &$el) {
            $res[] = [$el];
        }
        $i -= 1;

        while ($i >= 0) {
            $res = union($data[$i], $res);
            $i -= 1;
        }

        return $res;
    }

    // Чинит уже готовые наборы
    // Разбивает словосочетание на массив и переносит части с '-' в конец
    function fix_line(array &$line) {
        $right_ord = [];
        $fix_ord = [];

        foreach ($line as &$combo) {
            $buf = explode(' ', $combo);
            foreach ($buf as &$el) {
                if ($el[0] == '-') {
                    $fix_ord[] = $el;
                }
                else {
                    $right_ord[] = $el;
                }
            }
        }

        $buf = [];
        foreach ($right_ord as &$el) {
            $buf[] = $el;
        }

        foreach ($fix_ord as &$el) {
            $buf[] = $el;
        }
        
        return $buf;
    }

    function fix_order(array &$result) {
        $final = [];

        foreach ($result as &$line) {
            $final[] = fix_line($line);
        }

        return $final;
    }

    $raw_data = read_data();
    $data = read_data();
    fix_errors($data);
    prep_data($data);
    $final = gen_kw($data);
    $final = fix_order($final);
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Тестовое Задание</title>
        <link rel="stylesheet" href="style.css">
    </head>

    <body>
        <div class="main_screen">
            <div class="wrapper">
                <h2>Генерация ключевых слов</h2>
                <h3>Ваш запрос:</h3>
                <p>
                    <?php
                        foreach ($raw_data as &$line) {
                            foreach ($line as &$combo) {
                                echo($combo . ', ');
                            }
                            echo('<br>');
                        }
                    ?>
                </p>
                <h3>Результат:</h3>
                <p>
                    <ul>
                        <?php
                            foreach ($final as &$line) {
                                echo('<li>');
                                foreach ($line as $kw) {
                                    echo($kw . ' ');
                                }
                                echo('</li>');
                            }
                        ?>
                    </ul>
                </p>
            </div>
        </div>
    </body>
</html>