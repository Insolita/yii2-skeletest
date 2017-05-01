<?php
/**
 * Created by solly [29.04.17 21:31]
 */

namespace insolita\skeletest\controllers;

use insolita\skeletest\models\AppInfo;
use insolita\skeletest\models\Reflector;
use insolita\skeletest\models\TargetFile;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\console\Controller;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\helpers\FileHelper;

/**
 * Class SkeletestController
 *
 * @package insolita\skeletest\controlers
 */
class SkeletestController extends Controller
{
    /**
     * Application name
     *
     * @var string
     */
    public $app = 'common';
    
    /**
     * @var array
     */
    public $pathMap
        = [
            'backend' => [
                'appPath'  => '@backend/',
                'testPath' => '@backend/tests',
                'testNs'   => 'backend\tests\unit',
                'testerNs' => 'backend\tests\UnitTester',
            ],
            'front'   => [
                'appPath'  => '@frontend/',
                'testPath' => '@frontend/tests',
                'testNs'   => 'frontend\tests\unit',
                'testerNs' => 'frontend\tests\UnitTester',
            ],
            'common'  => [
                'appPath'  => '@common/',
                'testPath' => '@common/tests/unit',
                'testNs'   => 'common\tests\unit',
                'testerNs' => 'common\tests\UnitTester',
            ],
            'console' => [
                'appPath'  => '@console/',
                'testPath' => '@console/tests/unit',
                'testNs'   => 'console\tests\unit',
            ],
        ];
    
    /**
     * Path to test template
     *
     * @var string
     */
    public $templateFile = '@vendor/insolita/yii2-skeletest/templates/codeception.php';
    
    /**
     * Array of regexp patterns for files skipping  if matched
     * Only for generation by directory
     *
     * @var array
     */
    public $ignoreFilePatterns = ['~(controllers|widget|interface|asset|contract|migration)~i'];
    
    /**
     * Array of regexp patterns for methods skipping  if matched
     *
     * @var array
     */
    public $ignoreMethodPatterns = ['~^(behaviors|find|rules|tableName|attributeLabels|scenarios)$~'];
    
    /**
     * Overwrite existed files?
     *
     * @var bool
     */
    public $overwrite = false;
    
    /**
     * Skip getter methods
     *
     * @var bool
     */
    public $ignoreGetters = true;
    
    /**
     * Skip setter methods
     *
     * @var bool
     */
    public $ignoreSetters = true;
    
    /**
     * @var bool
     */
    public $withProtectedMethods = false;
    
    /**
     * @var bool
     */
    public $withPrivateMethods = false;
    
    /**
     * @var bool
     */
    public $withStaticMethods = false;
    
    /**
     * Show details about files
     *
     * @var bool
     */
    public $verbose = false;
    
    /**
     * @var string
     */
    public $defaultAction = 'file';
    
    /**
     * @var AppInfo
     */
    private $currentApp;
    
    /**
     * @var Reflector
     */
    private $reflector;
    
    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();
        $appInfo = ArrayHelper::getValue($this->pathMap, $this->app);
        if (!$appInfo) {
            throw new InvalidParamException('app not declared in pathMap');
        }
        if ($appInfo instanceof AppInfo) {
            $this->currentApp = $appInfo;
        } else {
            $this->currentApp = new AppInfo($appInfo);
        }
        $this->templateFile = FileHelper::normalizePath(Yii::getAlias($this->templateFile));
        if (!$this->templateFile || !file_exists($this->templateFile)) {
            throw new InvalidConfigException('template file not found');
        }
    }
    
    /**
     * @param \yii\base\Action $action
     *
     * @return bool
     */
    public function beforeAction($action)
    {
        if ($this->ignoreGetters === true) {
            $this->ignoreMethodPatterns[] = '~^get(.*)$~i';
        }
        if ($this->ignoreSetters === true) {
            $this->ignoreMethodPatterns[] = '~^set(.*)$~i';
        }
        $this->reflector = new Reflector(
            $this->withPrivateMethods,
            $this->withProtectedMethods,
            $this->withStaticMethods
        );
        return parent::beforeAction($action);
    }
    
    /**
     * @param string $actionID
     *
     * @return array
     */
    public function options($actionID)
    {
        return array_merge(
            parent::options($actionID),
            [
                'overwrite',
                'verbose',
                'app',
                'ignoreSetters',
                'ignoreGetters',
                'withProtectedMethods',
                'withPrivateMethods',
                'withStaticMethods',
            ]
        );
    }
    
    /**
     * @return array
     */
    public function optionAliases()
    {
        return array_merge(
            parent::optionAliases(),
            [
                'h'  => 'help',
                'o'  => 'overwrite',
                'is' => 'ignoreSetters',
                'ig' => 'ignoreGetters',
                'wp' => 'withProtectedMethods',
                'wv' => 'withPrivateMethods',
                'ws' => 'withStaticMethods',
                'v'  => 'verbose',
            ]
        );
    }
    
    /**
     * @param $alias
     *
     * @throws \yii\base\InvalidParamException
     */
    public function actionFile($alias)
    {
        $file = new TargetFile($alias, $this->currentApp);
        //Skip file filters for direct call
        if ($this->confirmGeneration([$file])) {
            $methods = $this->filterMethods($this->reflector->extractMethods($file->getClassName()));
            $this->generateSkeleton($file, $methods);
        }
        Console::output(Console::ansiFormat('Done!' . PHP_EOL, [Console::FG_GREEN]));
    }
    
    /**
     * @param $alias
     *
     * @throws \yii\base\InvalidParamException
     */
    public function actionDir($alias)
    {
        $targetFiles = $this->filterFiles(TargetFile::createFromDirectory($alias, $this->currentApp));
        if (empty($targetFiles)) {
            Console::output('php files not found by path ' . Yii::getAlias($alias));
        } else {
            if ($this->confirmGeneration($targetFiles)) {
                foreach ($targetFiles as $file) {
                    $methods = $this->filterMethods($this->reflector->extractMethods($file->getClassName()));
                    if ($this->verbose) {
                        Console::output(Console::ansiFormat(' - Generate ' . $file->getPath(), [Console::FG_CYAN]));
                    }
                    $this->generateSkeleton($file, $methods);
                }
            }
            Console::output(Console::ansiFormat('Done!' . PHP_EOL, [Console::FG_GREEN]));
        }
    }
    
    /**
     * @param array|TargetFile[] $targetFiles
     *
     * @return array|TargetFile[]
     */
    private function filterFiles(array $targetFiles)
    {
        $targetFiles = array_filter(
            $targetFiles,
            function (TargetFile $file) {
                if ($file->isPathMatched($this->ignoreFilePatterns) === true) {
                    if ($this->verbose) {
                        Console::output(
                            Console::ansiFormat(
                                '    Skip ' . $file->getPath(),
                                [Console::FG_CYAN]
                            )
                        );
                    }
                    return false;
                }
                if ($this->overwrite == false && $file->isTestFileExists()) {
                    if ($this->verbose) {
                        Console::output(
                            Console::ansiFormat(
                                '    Skip Existed ' . $file->getPath(),
                                [Console::FG_CYAN]
                            )
                        );
                    }
                    return false;
                }
                return true;
            }
        );
        return $targetFiles;
    }
    
    /**
     * @param array $methods
     *
     * @return array
     */
    private function filterMethods(array $methods)
    {
        if (!empty($this->ignoreMethodPatterns) && !empty($methods)) {
            $methods = array_filter(
                $methods,
                function ($methodName) {
                    foreach ($this->ignoreMethodPatterns as $pattern) {
                        if (preg_match($pattern, $methodName)) {
                            if ($this->verbose) {
                                Console::output(
                                    Console::ansiFormat(
                                        '  **Skip ' . $methodName . PHP_EOL,
                                        [Console::FG_YELLOW]
                                    )
                                );
                            }
                            return false;
                        }
                    }
                    return true;
                },
                ARRAY_FILTER_USE_KEY
            );
        }
        return $methods;
    }
    
    /**
     * @param array|TargetFile[] $targetFiles
     *
     * @return bool
     */
    private function confirmGeneration(array $targetFiles)
    {
        Console::output('The following tests will be created:' . PHP_EOL);
        foreach ($targetFiles as $file) {
            Console::output(Console::ansiFormat($file->getPath() . PHP_EOL, [Console::FG_GREEN]));
            if ($this->verbose) {
                Console::output(
                    '     -- ' . $file->getTestFullPath()
                    . ' with namespace ' . $file->getTestNs()
                    . PHP_EOL
                );
            }
        }
        return $this->confirm('Generate this tests?');
    }
    
    /**
     * @param TargetFile $file
     * @param array      $methods
     */
    private function generateSkeleton(TargetFile $file, array $methods)
    {
        $file->createTestDirectory();
        $useAccessibleTrait = ($this->withProtectedMethods === true || $this->withPrivateMethods === true);
        $data = $this->renderFile(
            $this->templateFile,
            [
                'targetFile'         => $file,
                'methods'            => $methods,
                'useAccessibleTrait' => $useAccessibleTrait,
            ]
        );
        $file->createTestFile($data);
    }
}
