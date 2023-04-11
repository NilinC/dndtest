<?php

namespace App\Tests\Command;

use App\Command\OutputProductsCommand;
use App\Formatter\Formatter;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class OutputProductsCommandTest extends KernelTestCase
{
    private const SUCCESS = 0;
    private const INVALID = 2;
    private const COMMAND_NAME = 'app:output-products';
    private const FILENAME_PATH = __DIR__.'/../../tests/dataProvider/products.csv';
    private const FILENAME_PATH_HTML = __DIR__.'/../../tests/dataProvider/products.html';

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

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(self::INVALID);
        $this->expectExceptionMessage('Le fichier fourni n\'a pas le bon type');
        $commandTester->execute([
            'command' => $command->getName(),
            'filename-path' => self::FILENAME_PATH_HTML
        ]);
    }

    /**
     * @return Application
     */
    private function initialize(): Application
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $formatter = $this->createMock(Formatter::class);
        $formatter->method('flatten')->willReturn([
            'sku', 'title', 'is_enabled', 'price', 'currency', 'description', 'created_at'
        ]);

        $application->add(new OutputProductsCommand($formatter));

        return $application;
    }
}
