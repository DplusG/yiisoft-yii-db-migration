<?php

declare(strict_types=1);

/**
 * This view is used by Yiisoft\Db\Yii\Migration\Command.
 *
 * The following variables are available in this view:
 */
/* @var $className string the new migration class name without namespace */
/* @var $namespace string the new migration class namespace */
/* @var $table string the name table */
/* @var $columns \Yiisoft\Yii\Db\Migration\Service\Generate\Column[] the fields */
/* @var $foreignKeys \Yiisoft\Yii\Db\Migration\Service\Generate\ForeignKey[] the foreign keys */

echo "<?php\n";

echo "\ndeclare(strict_types=1);\n";

if (!empty($namespace)) {
    echo "\nnamespace {$namespace};\n";
}
?>

use Yiisoft\Yii\Db\Migration\MigrationBuilder;
use Yiisoft\Yii\Db\Migration\RevertibleMigrationInterface;

/**
 * Handles dropping columns from table `<?= $table ?>`.
<?= $this->render('_foreignTables', [
    'foreignKeys' => $foreignKeys,
]) ?>
 */
final class <?= $className ?> implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
<?= $this->render('_dropColumns', [
    'table' => $table,
    'columns' => $columns,
    'foreignKeys' => $foreignKeys,
])
?>
    }

    public function down(MigrationBuilder $b): void
    {
<?= $this->render('_addColumns', [
    'table' => $table,
    'columns' => $columns,
    'foreignKeys' => $foreignKeys,
])
?>
    }
}
