<?php
/**
 * @var array $methods
 * @var \insolita\skeletest\models\TargetFile $targetFile
 * @var bool $useAccessibleTrait
 **/
echo "<?php".PHP_EOL;
echo "namespace {$targetFile->getTestNs()};".PHP_EOL.PHP_EOL;
?>
use Codeception\Test\Unit;
use Codeception\Specify;
<?php if($useAccessibleTrait===true):?>
use insolita\skeletest\AccessibleMethodTrait;
<?php endif;?>
<?php if($targetFile->getApp()->getTesterNs()):?>
use <?=$targetFile->getApp()->getTesterNs()?>;
<?php endif;?>
/**
*  Class <?=$targetFile->getTestClass().PHP_EOL ?>
*  Test for <?=$targetFile->getClassName().PHP_EOL?>
**/
class <?= $targetFile->getTestClass() ?> extends Unit
{
    use Specify;
<?php if($useAccessibleTrait===true):?>
    use AccessibleMethodTrait;
<?php endif;?>
<?php if($targetFile->getApp()->getTesterBaseName()):?>
    /**
    * @var <?=$targetFile->getApp()->getTesterBaseName()?>
    */
    protected $tester;

    protected function _before()
    {
        $this->tester->haveFixtures([
        ]);
    }
<?php else:?>
    protected function _before()
    {
        //Initialize test
    }
<?php endif;?>

<?php foreach ($methods as $method=>$signature):?>
    /**
    * Test for <?=$method.PHP_EOL?>
    **/
    public function test<?=ucfirst($method)?>()
    {
        /**
         * TODO: test <?=$signature.PHP_EOL?>
        **/
    }
<?php endforeach;?>
}