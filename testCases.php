<?php
require __DIR__ . '/vendor/autoload.php';


$project = '/Users/davidcallizaya/NetBeansProjects/processmaker';
$project = '/Users/davidcallizaya/NetBeansProjects/ProcessMakerRE';

/* @var $loader Composer\Autoload\ClassLoader */
$loader = addComposerLoader($project . '/vendor/autoload.php');

$map = $loader->getClassMap();

$function = function (ReflectionClass $class) {
    if ($class->newInstanceWithoutConstructor() instanceof PHPUnit\Framework\TestCase) {
        $card = [
            'title'              => '',
            'description'        => '',
            'module'             => '',
            'module-description' => '',
            'pre-conditions'     => [],
            'steps'              => [],
        ];
        $doc = $class->getDocComment();
        $card['module'] = $class->getName();
        if ($doc) {
            $card['module-description'] = docBlockParse($doc)->getSummary();
        }
        /* @var $method ReflectionMethod */
        foreach ($class->getMethods() as $method) {
            if (strtolower(substr($method->getName(), 0)) === 'setup') {
                $comments = getInlineCommentsInCode(getCodeFromReflectionMethod($method), '');
                $preCondition = docBlockParse($method->getDocComment())->getSummary();
                $card['pre-conditions'][$preCondition] = $comments;
            }
        }
        /* @var $method ReflectionMethod */
        foreach ($class->getMethods() as $method) {
            if (strtolower(substr($method->getName(), 0, 4)) === 'test') {
                $testDoc = $method->getDocComment();
                $card['title'] = $testDoc ? docBlockParse($testDoc)->getSummary() : $method->getName();
                $card['description'] = $testDoc ? docBlockParse($testDoc)->getDescription() : '';
                $comments = getInlineCommentsInCode(getCodeFromReflectionMethod($method), '');
                $card['steps'] = [[]];
                $card['results'] = [[]];
                $index = 0;
                $type = 0;
                foreach ($comments as $comment) {
                    if (strpos($comment[0], 'Assert:') === 0) {
                        $type = 1;
                        $card['results'][$index][] = trim(substr($comment[0], 7));
                    } else {
                        if ($type) {
                            $index++;
                        }
                        $type = 0;
                        $card['steps'][$index][] = $comment;
                    }
                }
                include 'data/template.php';
            }
        }
    }
};
ob_start();
array_filter(findClasses("$project/tests/Feature", 'Tests\Feature'), $function);
file_put_contents('output/test_cases.html', ob_get_contents());
ob_end_clean();

/**
 *
 * @param string $docComment
 *
 * @return \phpDocumentor\Reflection\DocBlock
 */
function docBlockParse($docComment)
{
    $factory = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
    return $factory->create($docComment);
}

/**
 *
 * @param ReflectionMethod $method
 * @return type
 */
function getCodeFromReflectionMethod(ReflectionMethod $method)
{
    $source = explode("\n", file_get_contents($method->getFileName()));
    $res = array_slice($source, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1);
    return implode("\n", $res);
}

/**
 *
 * @param type $code
 * @param type $prefix
 * @return string
 */
function getInlineCommentsInCode($code, $prefix)
{
    $tokens = token_get_all("<?php\n$code");
    $res = [];
    $code = [];
    $index = -1;
    foreach ($tokens as $token) {
        if (is_array($token) && $token[0] === T_COMMENT) {
            $res[] = [$prefix . trim(substr($token[1], 2)), ''];
            $index++;
        } elseif ($index >= 0) {
            $res[$index][1] .= is_array($token) ? $token[1] : $token;
        }
    }
    return $res;
}
