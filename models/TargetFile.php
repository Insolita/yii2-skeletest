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
     * @var string
     */
    private $path;
    /**
     * @var string
     */
    private $alias;
    
    /**
     * @var AppInfo
     */
    private $app;
    
    /**
     * @var string
     */
    private $className;
    
    /**
     * @var string
     */
    private $testFilePath;
    
    /**
     * @var string
     */
    private $testNs;
    
    /**
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
        if (!StringHelper::endsWith($path, '.php')) {
            $path .= '.php';
        }
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidParamException('Path must be a readable file');
        }
        $this->path = $path;
        $this->prepare();
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
    
    /**
     * @return \insolita\skeletest\models\AppInfo
     */
    public function getApp()
    {
        return $this->app;
    }
    
    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
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
            $targetFiles[] = new self($baseAlias.DIRECTORY_SEPARATOR.$relativeAlias, $appInfo);
        }
        return $targetFiles;
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
        $baseAlias = explode(DIRECTORY_SEPARATOR, $this->alias)[0];
        $relativeAlias = ltrim(str_replace(\Yii::getAlias($baseAlias), '', $this->path), '/');
        $this->className = FileHelper::normalizePath(str_replace(['@','.php'],
                                                           '',
                                                           $baseAlias.DIRECTORY_SEPARATOR.$relativeAlias
                                               ),'\\');
    }
}
