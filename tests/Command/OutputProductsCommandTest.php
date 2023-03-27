<?php

namespace App\Tests\Command;

use App\Command\OutputProductsCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class OutputProductsCommandTest extends KernelTestCase
{
    private const SUCCESS = 0;
    private const INVALID = 2;
    private const COMMAND_NAME = 'app:output-products';
    private const FILENAME_PATH = __DIR__.'/../../tests/dataProvider/products.csv';
    private const FILENAME_HTML = __DIR__.'/../../tests/dataProvider/products.html';

    public function testCommandSuccess()
    {
        $application = $this->initialize();

        $command = $application->find(self::COMMAND_NAME);
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'filename-path' => self::FILENAME_PATH
        ]);

        $fileExtension = pathinfo(self::FILENAME_PATH, PATHINFO_EXTENSION);

        $this->assertFileExists(self::FILENAME_PATH);
        $this->assertIsReadable(self::FILENAME_PATH);
        $this->assertSame('csv', $fileExtension);
        $this->assertNotEmpty($commandTester->getDisplay());
        $this->assertIsInt($commandTester->getStatusCode());
        $this->assertEquals(self::SUCCESS, $commandTester->getStatusCode(), 'Code retour commande success');
    }

    public function testCommandFailWithWrongFileType()
    {
        $application = $this->initialize();

        $command = $application->find(self::COMMAND_NAME);
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            'filename-path' => self::FILENAME_HTML
        ]);

        $fileExtension = pathinfo(self::FILENAME_HTML, PATHINFO_EXTENSION);

        $this->assertNotSame('csv', $fileExtension);
        $this->assertEquals(self::INVALID, $commandTester->getStatusCode(), 'Code retour commande invalide');
    }

    private function initialize()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $application->add(new OutputProductsCommand());

        return $application;
    }
}
