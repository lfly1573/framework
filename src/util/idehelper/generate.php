<?php

@set_time_limit(0);
require '../../init.php';

echo "\n---------- begin ----------\n";

$files = [];
foreach (glob(LFLY_PATH . 'facade/*' . EXT) as $filename) {
    $files[] = $filename;
}

$cacheFile = __DIR__ . DS . '_ide_helper.php';
$classStr = "<?php\n\n";
foreach ($files as $filename) {
    echo "{$filename}\n";
    $classStr .= genClassHelper('\\lfly\\' . basename($filename, EXT));
}
if (@$fp = fopen($cacheFile, 'wb')) {
    flock($fp, LOCK_EX);
    fwrite($fp, $classStr);
    flock($fp, LOCK_UN);
    fclose($fp);
}

echo "---------- end! ----------\n\n";

function genClassHelper($class)
{
    $info = getInfo($class);
    $return = '';
    if (!empty($info)) {
        $return = 'class ' . basename(str_replace('\\', '/', $class)) . "\n{\n\n";
        foreach ($info as $value) {

            $paramStr = '';
            foreach ($value['param'] as $param) {
                if ($paramStr != '') {
                    $paramStr .= ', ';
                }
                $paramStr .= '$' . $param['name'];
                if ($param['optional']) {
                    if (isset($param['default'])) {
                        if (is_numeric($param['default'])) {
                            $paramStr .= " = {$param['default']}";
                        } elseif (is_bool($param['default'])) {
                            $paramStr .= ' = ' . ($param['default'] ? 'true' : 'false');
                        } elseif (is_array($param['default'])) {
                            $paramStr .= ' = []';
                        } elseif (is_string($param['default'])) {
                            $paramStr .= ' = \'' . addcslashes(strval($param['default']), '\'') . '\'';
                        } else {
                            $paramStr .= ' = null';
                        }
                    } else {
                        $paramStr .= ' = null';
                    }
                }
            }
            if ($value['structor']) {
                $return .= "    {$value['comment']}\n    public function {$value['method']}({$paramStr}) {}\n\n";
            } elseif (substr($value['method'], 0, 2) != '__') {
                $return .= "    {$value['comment']}\n    public static function {$value['method']}({$paramStr}) {}\n\n";
            }
        }
        $return .= "}\n\n\n";
    }
    return $return;
}

function getInfo($class)
{
    try {
        $reflect = new ReflectionClass($class);
    } catch (ReflectionException $e) {
        throw new LogicException('class not exists: ' . $class);
    }
    $return = [];
    $methods = $reflect->getMethods(ReflectionMethod::IS_PUBLIC);
    foreach ($methods as $method) {
        $comment = $method->getDocComment();
        $curdata = ['class' => $method->class, 'method' => $method->name, 'static' => $method->isStatic(), 'structor' => ($method->isConstructor() || $method->isDestructor()), 'param' => [], 'comment' => $comment];
        $param = $method->getParameters();
        foreach ($param as $value) {
            $tempparam = ['name' => $value->name, 'type' => $value->getType(), 'optional' => false];
            if ($value->isOptional()) {
                $tempparam['optional'] = true;
                $tempparam['default'] = $value->getDefaultValue();
            }
            $curdata['param'][] = $tempparam;
        }
        $return[] = $curdata;
    }
    return $return;
}
