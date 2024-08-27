<?php

namespace Rachyharkov\CodeigniterMedia\Commands;

use Config\Autoload;
use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\BaseCommand;

/**
 * @package   CodeIgniter Media Library: Resource Publishing Command
 * @category  Libraries
 * @author    Rachmad Nur Hayat <rachmadnurhayat@gmail.com>
 * @license   http://opensource.org/licenses/MIT > MIT License
 * @link      https://github.com/rachyharkov
 *
 * CodeIgniter Media Library. It allows you to associate files very easily with models.
 */
class Publish extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     *
     * @var string
     */
    protected $group = 'Media';

    /**
     * The Command's name
     *
     * @var string
     */
    protected $name = 'media:publish';

    /**
     * the Command's short description
     *
     * @var string
     */
    protected $description = 'Publish media library functionality into the current application.';

    /**
     * the Command's usage
     *
     * @var string
     */
    protected $usage = 'media:publish';

    /**
     * the Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * the Command's Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * The path to Rachyharkov\CodeigniterMedia source
     *
     * @var string
     */
    protected $sourcePath;

    //--------------------------------------------------------------------

    /**
     * Displays the help for the spark cli script itself.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        $this->determineSourcePath();
        CLI::write('Publishing Migration...');
        $this->publishMigration();
        CLI::write('Publishing Model...');
        $this->publishModel();

        CLI::write('Successfully published Media library assets.');
    }

    protected function publishMigration()
    {
        $path = "{$this->sourcePath}/assets/Migrations/2021-09-01-000000_create_media_tables.php";
        $content = file_get_contents($path);  
        $this->writeFile("Database/Migrations/2021-09-01-000000_create_media_tables.php", $content);

    }

    protected function publishModel()
    {
        $path = "{$this->sourcePath}/assets/Model/Media.php";
        $content = file_get_contents($path);
        $content = $this->replaceNamespace($content, 'App\Models', 'Models');
        $this->writeFile("Models/Media.php", $content);
    }



    //--------------------------------------------------------------------
    // Utilities
    //--------------------------------------------------------------------

    /**
     * Replaces the Rachyharkov\CodeigniterMedia namespace in the published
     * file with the applications current namespace.
     *
     * @param string $contents
     * @param string $originalNamespace
     * @param string $newNamespace
     *
     * @return string
     */
    protected function replaceNamespace(string $contents, string $originalNamespace, string $newNamespace): string
    {
        $appNamespace = APP_NAMESPACE;
        $originalNamespace = "namespace {$originalNamespace}";
        $newNamespace = "namespace {$appNamespace}\\{$newNamespace}";

        return str_replace($originalNamespace, $newNamespace, $contents);
    }

    /**
     * Determines the current source path from which all other files are located.
     */
    protected function determineSourcePath()
    {
        $this->sourcePath = realpath(__DIR__ . '/../');

        if ($this->sourcePath == '/' || empty($this->sourcePath)) {
            CLI::error('Unable to determine the correct source directory. Bailing.');
            exit();
        }
    }

    /**
     * Write a file, catching any exceptions and showing a
     * nicely formatted error.
     *
     * @param string $path
     * @param string $content
     */
    protected function writeFile(string $path, string $content)
    {
        $config = new Autoload();
        $appPath = $config->psr4[APP_NAMESPACE];

        $directory = dirname($appPath . $path);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        try {
            write_file($appPath . $path, $content);
        } catch (\Exception $e) {
            $this->showError($e);
            exit();
        }

        $path = str_replace($appPath, '', $path);

        CLI::write(CLI::color('  published: ', 'green') . $path);
    }
}