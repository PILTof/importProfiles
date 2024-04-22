<?php
namespace \Controller;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


class catalogImport
{
    private string $filepath;

    private string $ext;

    private array $convertRes = [];

    private static $keys = [];

    private static function searchKeys(array $value) {
        if(is_array($value)) {
            $a = array_filter($value, fn($q) => is_array($q));
            $resVals = array_filter($value, fn($q) => !is_array($q));
            if(count($a) > 0) {
                foreach ($a as $key => $uderval) {
                    self::searchKeys($uderval);
                }
            }
            if(count($resVals) > 0) {
                self::$keys = array_merge(self::$keys, array_filter(array_keys($resVals), fn($q) => !is_numeric($q)));
            }
        }
    }
    private static array $multiplePropertyContainers = [];
    private static function searchMultiplePropertyKeys(array $value) {
        if(is_array($value)) {
            $a = array_filter($value, fn($q) => is_array($q));
            self::$multiplePropertyContainers = array_merge(self::$multiplePropertyContainers, array_keys($a));
            foreach ($a as $container) {
                self::searchMultiplePropertyKeys($container);
            }
        }

    }

    public static function getMultiplePropertyContainer(array $values) {
        self::searchMultiplePropertyKeys($values);
        return array_unique(self::$multiplePropertyContainers);
    }
    public static function getMultipleKeys(array $array) {
        self::$keys = [];
        self::searchKeys($array);

        return array_unique(self::$keys);
    }
    public function __construct(string $filepath) {
        $this->filepath = $filepath;
        $this->ext = pathinfo($filepath, PATHINFO_EXTENSION);
    }

    private function getExamples(array $array) {
        if(!is_array($array)) return null;
        if(count(array_keys($array)) > 1) {
            return $array;
        }
        return $this->getExamples(reset($array));
    }

    private function getProperties(array $array) {
        $res = [];
        $diff = array_diff(array_values($array));
        foreach ($diff as $key => $val) {
            $res = array_merge($res, $val);
        }
        return $res;
    }

    private function fromJson ():array|null {
        $res = null;
        if(!file_exists($this->filepath)) return $res;
        $content = json_decode(file_get_contents($this->filepath), true);
        $examples = $this->getExamples($content);
        $props = $this->getProperties($examples);
        $res = $props;

        return $res;
    }

    public function props() {
        switch ($this->ext) {
            case 'json':
                $this->convertRes = $this->fromJson() ?? [];
                break;
            case 'xlsx':
                # code...
                break;
            
            default:
                # code...
                break;
        }
        return $this;
    }

    public function get() {
        return $this->convertRes;
    }



    


}