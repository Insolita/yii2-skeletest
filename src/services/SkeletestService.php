<?php
/**
 * Created by solly [27.05.17 23:15]
 */

namespace insolita\skeletest\services;

use insolita\skeletest\entity\AppConfig;
use insolita\skeletest\entity\FileClass;
use insolita\validators\PathValidator;
use yii\base\DynamicModel;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;

/**
 * Class SkeletestService
 *
 * @package insolita\skeletest\services
 */
class SkeletestService
{
    /**
     * @param array $config
     *
     * @return \insolita\skeletest\entity\AppConfig
     * @throws \yii\base\InvalidConfigException
     */
    public function createAppConfig(array $config = []): AppConfig
    {
        if (!isset($config['appPath'], $config['testPath'], $config['testNs'])) {
            throw new InvalidConfigException('Wrong app configuration');
        }
        $testerNs = $config['testerNs']??'';
        return new AppConfig(
            \Yii::getAlias($config['appPath']),
            \Yii::getAlias($config['testPath']),
            $config['testNs'],
            $testerNs
        );
    }
    
    /**
     * @param string $pathAlias
     *
     * @return mixed
     * @throws \yii\base\InvalidParamException
     */
    public function getValidFilePath(string $pathAlias)
    {
        $path = $this->appendExtension($pathAlias, 'php');
        $model = DynamicModel::validateData(
            ['path' => $path],
            [
                ['path', PathValidator::class, 'readable' => true, 'strictFile' => true],
            ]
        );
        if ($model->hasErrors()) {
            throw new InvalidParamException($model->getFirstError('path'));
        } else {
            return $model->path;
        }
    }
    
    /**
     * @param string $pathAlias
     *
     * @return mixed
     * @throws \yii\base\InvalidParamException
     */
    public function getValidDirectoryPath(string $pathAlias)
    {
        $model = DynamicModel::validateData(
            ['path' => $pathAlias],
            [
                ['path', PathValidator::class, 'readable' => true, 'strictDir' => true],
            ]
        );
        if ($model->hasErrors()) {
            throw new InvalidParamException($model->getFirstError('path'));
        } else {
            return $model->path;
        }
    }
    
    /**
     * @param string $path
     *
     * @return \insolita\skeletest\entity\FileClass
     */
    public function createFileClass(string $path): FileClass
    {
        $name = pathinfo($path, PATHINFO_FILENAME);
        $namespace = $this->extractNamespace(file_get_contents($path));
        return new FileClass($path, $name, $namespace, $namespace . '\\' . $name);
    }
    
    /**
     * @param string    $directoryPath
     * @param AppConfig $app
     *
     * @return array|FileClass[][]
     * @throws \yii\base\InvalidParamException
     */
    public function createFileClassesFromDirectory($directoryPath, AppConfig $app): array
    {
        $files = FileHelper::findFiles($directoryPath, ['only' => ['*.php'], 'recursive' => true]);
        $targetFiles = [];
        foreach ($files as $filePath) {
            $fileClass = $this->createFileClass($filePath);
            if ($fileClass->getNamespace()) {
                $testFile = $this->guessTestFileClass($fileClass, $app);
            } else {
                $testFile = null;
            }
            $targetFiles[] = [$fileClass, $testFile];
        }
        return $targetFiles;
    }
    
    /**
     * @param \insolita\skeletest\entity\FileClass $file
     * @param \insolita\skeletest\entity\AppConfig $config
     *
     * @return \insolita\skeletest\entity\FileClass
     */
    public function guessTestFileClass(FileClass $file, AppConfig $config): FileClass
    {
        $name = $file->getName() . 'Test';
        $fileDir = dirname($file->getPath());
        $relativePath = str_replace($config->getAppPath(), '', $fileDir);
        $testPath = FileHelper::normalizePath($config->getTestPath() . '/' . $relativePath . '/' . $name . '.php');
        $testNamespace = FileHelper::normalizePath($config->getTestNs() . '\\' . $relativePath,'\\');
        return new FileClass($testPath, $name, $testNamespace, $testNamespace . '\\' . $name);
    }
    
    /**
     * @param array $methods
     * @param array $ignorePatterns
     *
     * @return array
     */
    public function filterMethodsByPattern(array $methods, array $ignorePatterns)
    {
        $skipped = [];
        if (!empty($ignorePatterns) && !empty($methods)) {
            $methods = array_filter(
                $methods,
                function ($methodName) use ($ignorePatterns, &$skipped) {
                    foreach ($ignorePatterns as $pattern) {
                        if (preg_match($pattern, $methodName)) {
                            $skipped[] = $methodName;
                            return false;
                        }
                    }
                    return true;
                },
                ARRAY_FILTER_USE_KEY
            );
        }
        return [$methods, $skipped];
    }
    
    /**
     *
     */
    public function createTestDirectory($path)
    {
        if (!is_dir($path)) {
            FileHelper::createDirectory($path, 0777);
        }
    }
    
    /**
     * @param $data
     */
    public function createTestFile($path, $data)
    {
        file_put_contents($path, $data);
    }
    
    /**
     * @param array $filePatterns
     *
     * @return bool
     */
    public function isPathMatched($path, array $filePatterns = [])
    {
        if (!empty($filePatterns)) {
            foreach ($filePatterns as $pattern) {
                if (preg_match($pattern, $path)) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * @param string $content
     *
     * @return string
     */
    protected function extractNamespace(string $content): string
    {
        preg_match('/^\s*namespace\s*([\\\\\w]+);\s*$/mis', $content, $matches);
        return $matches[1]??'';
    }
    
    /**
     * @param $path
     * @param $extension
     *
     * @return string
     */
    protected function appendExtension(string $path, string $extension): string
    {
        if (!StringHelper::endsWith($path, '.' . $extension)) {
            $path .= '.' . $extension;
        }
        return $path;
    }
}
