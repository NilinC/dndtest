<?php

namespace App\Tests\Command;

use App\Command\OutputProductsCommand;
use App\Formatter\ProductFormatter;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class OutputProductsCommandTest extends KernelTestCase
{
    private const SUCCESS = 0;
    private const INVALID = 2;
    private const COMMAND_NAME = 'app:output-products';
    private const FILENAME_PATH = __DIR__ . '/../../tests/dataProvider/products.csv';
    private const FILENAME_PATH_HTML = __DIR__ . '/../../tests/dataProvider/products.html';

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

        $formatter = $this->createMock(ProductFormatter::class);
        $formatter->method('createProductsRows')->willReturn([
            [
                "Sku" => "628937273",
                "Status" => "Enable",
                "Price" => "14,87€",
                "Description" => "Cornelia, \n
                  The Dark Unicorn.",
                "CreatedAt" => "Wednesday, 12-Dec-18 11:34:39 CET",
                "Slug" => "cornelia-the-dark-unicorn",
            ],
            [
                "Sku" => "722821313",
                "Status" => "Disable",
                "Price" => "18,80€",
                "Description" => "Be \n
                  my bestie, darling sweet.",
                "CreatedAt" => "Wednesday, 12-Dec-18 11:34:39 CET",
                "Slug" => "be-my-bestie",
            ]
        ]);

        $application->add(new OutputProductsCommand($formatter));

        return $application;
    }
}
