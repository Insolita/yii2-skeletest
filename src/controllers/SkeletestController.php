<?php
/**
 * Created by solly [27.05.17 23:18]
 */

namespace insolita\skeletest\controllers;

use insolita\skeletest\entity\AppConfig;
use insolita\skeletest\entity\FileClass;
use insolita\skeletest\services\SkeletestService;
use insolita\skeletest\services\ReflectionService;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\console\Controller;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;

/**
 * Class SkeletestController
 *
 * @package insolita\skeletest\controllers
 */
class SkeletestController extends Controller
{
    /**
     * List with configs of available applications
     *
     * @var array
     */
    public $apps
        = [
        
        ];
    
    /**
     * Current application config key from $apps list
     *
     * @var string
     */
    public $app = 'app';
    
    /**
     * Path to test template
     *
     * @var string
     */
    public $templateFile = '@vendor/insolita/yii2-skeletest/src/templates/codeception.php';
    
    /**
     * Array of regexp patterns for files skipping  if matched
     * Only for generation by directory
     *
     * @var array
     */
    public $ignoreFilePatterns = ['~(controllers|widget|interface|asset|event|contract|migration|exception)~i'];
    
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
    
    public $serviceClass = SkeletestService::class;
    
    public $reflectionServiceClass = ReflectionService::class;
    
    /**
     * @var ReflectionService
     */
    private $reflectionService;
    
    /**
     * @var SkeletestService
     */
    private $service;
    
    /**
     * @var \insolita\skeletest\entity\AppConfig
     */
    private $currentApp;
    
    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();
        $this->templateFile = \Yii::getAlias($this->templateFile);
        if (!$this->templateFile || !file_exists($this->templateFile)) {
            throw new InvalidConfigException('template file not found');
        }
    }
    
    /**
     * @param \yii\base\Action $action
     *
     * @throws \yii\base\InvalidParamException
     * @return bool
     */
    public function beforeAction($action)
    {
        $appInfo = ArrayHelper::getValue($this->apps, $this->app);
        if (!$appInfo) {
            throw new InvalidParamException('app with key ' . $this->app . ' not declared in apps configuration');
        }
        $this->service = \Yii::createObject($this->serviceClass);
        if ($appInfo instanceof AppConfig) {
            $this->currentApp = $appInfo;
        } else {
            $this->currentApp = $this->service->createAppConfig($appInfo);
        }
        if ($this->ignoreGetters === true) {
            $this->ignoreMethodPatterns[] = '~^get(.*)$~i';
        }
        if ($this->ignoreSetters === true) {
            $this->ignoreMethodPatterns[] = '~^set(.*)$~i';
        }
        $this->reflectionService = \Yii::createObject(
            $this->reflectionServiceClass,
            [
                $this->withPrivateMethods,
                $this->withProtectedMethods,
                $this->withStaticMethods,
            ]
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
        $path = $this->service->getValidFilePath($alias);
        $file = $this->service->createFileClass($path);
        if (!$file->getNamespace()) {
            Console::output(
                Console::ansiFormat(
                    'File without namespace, or namespace not recognized' . PHP_EOL,
                    [Console::FG_RED]
                )
            );
            return 1;
        }
        
        $testFile = $this->service->guessTestFileClass($file, $this->currentApp);
        //Skip file filters for direct call
        if ($this->confirmGeneration([[$file,$testFile]])) {
            $reflection = new \ReflectionClass($file->getFqnClass());
            $methods = $this->reflectionService->extractMethods($reflection);
            list($methods, $skipped) = $this->service->filterMethodsByPattern($methods, $this->ignoreFilePatterns);
    
            if ($this->verbose && !empty($skipped)) {
                Console::output(
                    Console::ansiFormat(
                        '  **Skip methods '.PHP_EOL . implode(PHP_EOL, $skipped). PHP_EOL,
                        [Console::FG_YELLOW]
                    )
                );
            }
            $this->generateSkeleton($testFile, $methods, $reflection);
            
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
        $path = $this->service->getValidDirectoryPath($alias);
        $files = $this->service->createFileClassesFromDirectory($path,$this->currentApp);
        $targetFiles = [];
        foreach ($files as $fileset) {
            /**@var FileClass $file*/
            /**@var FileClass $testFile*/
            list($file,$testFile) = $fileset;
            if(!$testFile){
                Console::output(
                    Console::ansiFormat(
                        'File without namespace, or namespace not recognized' . PHP_EOL,
                        [Console::FG_RED]
                    )
                );
                continue;
            }
            if ($this->service->isPathMatched($file->getPath(), $this->ignoreFilePatterns) === true) {
                if ($this->verbose) {
                    Console::output(Console::ansiFormat(' - Skip ' . $file->getPath(), [Console::FG_CYAN]));
                }
                continue;
            }
            if($this->overwrite === false && file_exists($testFile->getPath())){
                if($this->verbose){
                    Console::output(Console::ansiFormat(' ** Skip Existed' . $file->getPath(), [Console::FG_YELLOW]));
                }
                continue;
            }
            $targetFiles[] = [$file,$testFile];
        }
        if (empty($targetFiles)) {
            Console::output('php files not found by path ' . \Yii::getAlias($alias));
        } else {
            if ($this->confirmGeneration($targetFiles)) {
                foreach ($targetFiles as $fileset) {
                    /**@var FileClass $file*/
                    /**@var FileClass $testFile*/
                    list($file,$testFile) = $fileset;
                    $reflection = new \ReflectionClass($file->getFqnClass());
                    $methods = $this->reflectionService->extractMethods($reflection);
                    list($methods, $skipped) =
                        $this->service->filterMethodsByPattern($methods, $this->ignoreFilePatterns);
    
                    if ($this->verbose && !empty($skipped)) {
                        Console::output(
                            Console::ansiFormat(
                                '  **Skip methods '.PHP_EOL . implode(PHP_EOL, $skipped). PHP_EOL,
                                [Console::FG_YELLOW]
                            )
                        );
                    }
                    $this->generateSkeleton($testFile, $methods, $reflection);
                }
            }
            Console::output(Console::ansiFormat('Done!' . PHP_EOL, [Console::FG_GREEN]));
        }
    }
    
    /**
     * @param array|FileClass[]
     *
     * @return bool
     */
    private function confirmGeneration(array $files)
    {
        Console::output('The following tests will be created:' . PHP_EOL);
        foreach ($files as $fileset) {
            /**@var FileClass $file*/
            /**@var FileClass $testFile*/
            list(,$testFile) = $fileset;
            Console::output(Console::ansiFormat($testFile->getPath() . PHP_EOL, [Console::FG_GREEN]));
            if ($this->verbose) {
                Console::output(
                    '     -- ' . $testFile->getPath()
                    . ' with namespace ' . $testFile->getFqnClass()
                    . PHP_EOL
                );
            }
        }
        return $this->confirm('Generate this tests?');
    }
    
    /**
     * @param \insolita\skeletest\entity\FileClass $testFile
     * @param array      $methods
     * @param \ReflectionClass $reflection
     */
    private function generateSkeleton(FileClass $testFile, array $methods, \ReflectionClass $reflection)
    {
        $this->service->createTestDirectory(dirname($testFile->getPath()));
        $useAccessibleTrait = ($this->withProtectedMethods === true || $this->withPrivateMethods === true);
        $data = $this->renderFile(
            $this->templateFile,
            [
                'testFile'         => $testFile,
                'app' => $this->currentApp,
                'methods'            => $methods,
                'reflection' =>$reflection,
                'useAccessibleTrait' => $useAccessibleTrait,
            ]
        );
        $this->service->createTestFile($testFile->getPath(), $data);
    }
    
}
