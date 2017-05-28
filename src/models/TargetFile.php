<?php
/**
 * Created by solly [30.04.17 12:23]
 */

namespace insolita\skeletest\models;

use yii\base\InvalidParamException;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;

/**
 * Class TargetFile
 *
 * @package insolita\skeletest\models
 */
final class TargetFile
{
    
    /**
     * Full file path
     * @var string
     */
    private $path;
    
    /**
     * Path alias
     * @var string
     */
    private $alias;
    
    /**
     * @var AppInfo
     */
    private $app;
    
    /**
     * FQN className
     * @var string
     */
    private $className;
    
    /**
     * File namespace
     * @var string
     */
    private $ns;
    
    /**
     * File name, without extension
     * @var string
     */
    private $name;
    
    /**
     * Full path for future test file
     * @var string
     */
    private $testFilePath;
    
    /**
     * Namespace for future test
     * @var string
     */
    private $testNs;
    
    /**
     * FQN className for future test
     * @var string
     */
    private $testClass;
    
    /**
     * TargetFile constructor.
     *
     * @param string  $alias
     * @param AppInfo $app
     */
    public function __construct(string $alias, AppInfo $app)
    {
        $this->app = $app;
        $this->alias = $alias;
        $path = FileHelper::normalizePath(\Yii::getAlias($alias));
        $path = $this->appendExtension($path, 'php');
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidParamException('Path must be a readable file');
        }
        $this->path = $path;
        $this->name = pathinfo($path,PATHINFO_FILENAME);
        $this->ns = $this->namespaceMatcher(file_get_contents($this->path));
        $this->className = $this->ns . '\\' . $this->name;
        $this->prepare();
    }
    
    /**
     * @return \insolita\skeletest\models\AppInfo
     */
    public function getApp(): AppInfo
    {
        return $this->app;
    }
    
    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }
    
    /**
     * @return string
     */
    public function getNs(): string
    {
        return $this->ns;
    }
    
    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }
    
    /**
     * @param string $prefix
     * @param string $postfix
     *
     * @return string
     */
    public function getFileName($prefix = '', $postfix = '', $extension = '.php'): string
    {
        return $prefix . $this->name . $postfix . $extension;
    }
    
    /**
     * @return string
     */
    public function getTestFilePath()
    {
        return $this->testFilePath;
    }
    
    /**
     * @return string
     */
    public function getTestNs()
    {
        return $this->testNs;
    }
    
    /**
     * @return string
     */
    public function getTestClass()
    {
        return $this->testClass;
    }
    
    /**
     * @return string
     */
    public function getTestFileName()
    {
        return $this->testClass . '.php';
    }
    
    /**
     * @return string
     */
    public function getTestFullPath()
    {
        return $this->testFilePath . DIRECTORY_SEPARATOR . $this->getTestFileName();
    }
    
    /**
     * @param array $filePatterns
     *
     * @return bool
     */
    public function isPathMatched(array $filePatterns = [])
    {
        if (!empty($filePatterns)) {
            foreach ($filePatterns as $pattern) {
                if (preg_match($pattern, $this->path)) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * @return bool
     */
    public function isTestFileExists()
    {
        return file_exists($this->getTestFullPath());
    }
    
    /**
     *
     */
    public function createTestDirectory()
    {
        if (!is_dir($this->getTestFilePath())) {
            FileHelper::createDirectory($this->getTestFilePath(), 0777);
        }
    }
    
    /**
     * @param $data
     */
    public function createTestFile($data)
    {
        file_put_contents($this->getTestFullPath(), $data);
    }
    
    /**
     * @param $content
     *
     * @return string
     */
    public function namespaceMatcher($content): string
    {
        preg_match('/^\s*namespace\s*([\\\\\w]+);\s*$/mis', $content, $matches);
        return $matches[1]??'';
    }
    
    /**
     * @param                                    $alias
     * @param \insolita\skeletest\models\AppInfo $appInfo
     *
     * @return array|\insolita\skeletest\models\TargetFile[]
     * @throws \yii\base\InvalidParamException
     */
    public static function createFromDirectory($alias, AppInfo $appInfo)
    {
        $path = FileHelper::normalizePath(\Yii::getAlias($alias));
        if (!is_dir($path)) {
            throw new InvalidParamException('Path must be a directory');
        }
        if (!StringHelper::startsWith($path, $appInfo->getAppPath())) {
            throw new InvalidParamException('Target directory must belongs app directory');
        }
        $files = FileHelper::findFiles($path, ['only' => ['*.php'], 'recursive' => true]);
        $targetFiles = [];
        foreach ($files as $file) {
            $baseAlias = explode(DIRECTORY_SEPARATOR, $alias)[0];
            $relativeAlias = ltrim(str_replace(\Yii::getAlias($baseAlias), '', $file), '/');
            $targetFiles[] = new self($baseAlias . DIRECTORY_SEPARATOR . $relativeAlias, $appInfo);
        }
        return $targetFiles;
    }
    
    /**
     * @param $path
     * @param $extension
     *
     * @return string
     */
    protected function appendExtension(string $path, string $extension):string
    {
        if (!StringHelper::endsWith($path, '.' . $extension)) {
            $path .= '.' . $extension;
        }
        return $path;
    }
    
    /**
     *
     */
    private function prepare()
    {
        $relativePath = str_replace($this->app->getAppPath(), '', StringHelper::dirname($this->path));
        $this->testFilePath = FileHelper::normalizePath($this->app->getTestPath() . '/' . $relativePath);
        $this->testClass = StringHelper::basename($this->path, '.php') . 'Test';
        $this->testFileName = $this->testClass . '.php';
        $this->testNs = FileHelper::normalizePath($this->app->getTestNs() . '/' . $relativePath, '\\');
    }
}
