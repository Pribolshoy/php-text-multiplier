<?php
class TextParser
{
    protected $template = '';

    protected $patterns = [];

    protected $versions_count = 0;

    protected $string_versions = [];

    /**
     * [getResult description]
     *
     * @return array or boolean
     */
    public function getResult()
    {
        if (count($this->string_versions)) {
            return $this->string_versions;
        } else {
            return false;
        }
    }

    /**
     * [run description]
     *
     * @param string $str
     * 
     * @return TextParser
     */
    public function run($str)
    {
        if ($this->validate($str)) {
            $this->parseTemplate($str);
            $this->parsePatterns($str);
            $this->countTextVersions();
            $this->build();
        } else {
            die('Во входной строке обнаружена ошибка!');
        }

        return $this;
    }

    /**
     * [showStat description]
     *
     * @return void
     */
    public function showStat()
    {
        if ($this->string_versions) {
            print '<pre>';
            print_r('Шаблон: ' . $this->template);
            print '<br>';
            print_r('Патерны: ');
            print '<br>';
            print_r($this->patterns);
            print '<br>';
            print_r('Ожидаемое количество версий текста: ' . $this->versions_count);
            print '<br>';
            print_r('Версии: ');
            print_r($this->string_versions);
            print '</pre>';
        } else {
            print 'Нечего показывать!:(';
        }
    }

    /**
     * [validate description]
     *
     * @param string $str 
     * 
     * @return bool
     */
    protected function validate($str)
    {
        $count = strlen($str);
        $brackets = ['<', '>', ':'];

        $sifter = '';
        for ($i=0; $i < $count; $i++) { 
            if (in_array($str[$i], $brackets)) {
                $sifter .= $str[$i];
            }
        }

        $result = $this->clearBrackets($sifter);

        return (strlen($result)) ? false : true;
    }

    /**
     * [clearBrackets description]
     *
     * @param string $str 
     * @param array  $brakets 
     * 
     * @return string
     */
    protected function clearBrackets($str, $brakets = ['<>', '::'])
    {
        $result = str_replace($brakets, [''], $str);

        if ($result !== $str) {
            $result = $this->clearBrackets($result);
        }
        return $result;
    }

    /**
     * [parseTemplate description]
     *
     * @param string $str 
     * 
     * @return string
     */
    protected function parseTemplate($str)
    {
        $count = preg_match_all('#<(?>[^<>]+|(?R))*>#', $str, $result); // количество  патернов
        $matches = $result[0];

        for ($i=0; $i < $count; $i++) { 
            $str = str_replace($matches[$i], 'pattern' . $i, $str);
        }

        $this->template = $str;
    }

    /**
     * [parsePatterns description]
     *
     * @param string $str 
     * 
     * @return void
     */
    protected function parsePatterns($str)
    {
        $count = preg_match_all('#<(?>[^<>]+|(?R))*>#', $str, $result); // количество  патернов
        $matches = $result[0];

        for ($i=0; $i < $count; $i++) { 
            $this->patterns[] = $this->getPatternVersions($matches[$i], false);
        }
    }

    /**
     * [getPatternVersions description]
     *
     * @param string  $str         
     * @param boolean $cut_brakets 
     * 
     * @return array               
     */
    protected function getPatternVersions($str, $cut_brakets = true)
    {
        if ($cut_brakets) {
            $str = mb_substr($str, 1, mb_strlen($str) - 2);  // очищаем от скобок
        }
        
        $count = preg_match_all('#<(?>[^<>]+|(?R))*>#', $str, $result); // есть ли внутри подпатерны
        $variants = []; // вариации

        if ($count) {
            $newstr = '';
            for ($i=0; $i < $count; $i++) {
                $subpattern = $result[0][$i]; // значение подпатерна
                $sub = $this->getPatternVersions($result[0][$i]);

                foreach ($sub as $value) {
                    $newstr .= str_replace("$subpattern", $value, $str) . '::';
                }
            }
            $variants = $this->getSplit($newstr);
        } else {
            $variants = explode('::', $str);
        }

        return $variants;
    }

    /**
     * [getSplit description]
     *
     * @param string $string 
     * 
     * @return array         
     */
    protected function getSplit($string)
    {
        $results = [];
        $vals = explode('::', $string);
        foreach ($vals as $value) {
            $value = preg_replace('#\s+#', ' ', $value);
            $isnt_processed = preg_match('#(?>.*[<>].*)#', $value);
            if (!in_array($value, $results) && !empty($value) && !$isnt_processed) {
                $results[] = trim($value);
            }
        }
        return $results;
    }

    /**
     * [countTextVersions description]
     *
     * @return void
     */
    protected function countTextVersions()
    {
        if (!$this->patterns) {
            return 0;
        }

        $count = 1;
        foreach ($this->patterns as $values) {
            $count *= count($values);
        }
        $this->versions_count = $count;
    }

    /**
     * [build description]
     *
     * @param integer $num
     * 
     * @return array
     */
    protected function build($num = 0)
    {
        $res = [];

        if (array_key_exists($num+1, $this->patterns)) {
            $sub_res = $this->build($num+1);
        } else {
            $sub_res[] = $this->template;
        }
            
        for ($i=0; $i < count($sub_res); $i++) { 

            $versions_count = count($this->patterns[$num]);

            for ($p=0; $p < $versions_count; $p++) { 
                $text = $sub_res[$i]; 
                $text = str_replace('pattern'.$num, $this->patterns[$num][$p], $text);
                $res[] = $text;
            }
        }
        return $this->string_versions = $res;
    }
}
