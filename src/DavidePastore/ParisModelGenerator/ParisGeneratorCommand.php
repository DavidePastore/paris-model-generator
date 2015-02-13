<?php

namespace DavidePastore\ParisModelGenerator;

use DavidePastore\ParisModelGenerator\Config;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\PropertyGenerator;

/**
 * ParisGeneratorCommand class.
 *
 * @license MIT
 * @author Davide Pastore <pasdavide@gmail.com>
 */
class ParisGeneratorCommand extends Command
{
	private $force = false;
	
	/**
	 * 
	 * @var InputInterface
	 */
	private $input;
	
	/**
	 * 
	 * @var OutputInterface
	 */
	private $output;
	
	/**
	 * 
	 * @var Config
	 */
	private $config;
	
	/**
	 * (non-PHPdoc)
	 * @see \Symfony\Component\Console\Command\Command::configure()
	 */
	protected function configure()
    {
        $this
            ->setName('models')
            ->setDescription('Generate models')
            ->addOption(
               'force',
               null,
               InputOption::VALUE_NONE,
               'If set, files will be overwritten without ask confirmation.'
            )
        ;
    }

    /**
     * (non-PHPdoc)
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$this->input = $input;
		$this->output = $output;
		$this->force = $input->getOption('force');
		
		$helper = $this->getHelper('question');
		
        $userQuestion = new Question('User (root): ', 'root');
		
        $passwordQuestion = new Question('Password (): ', '');
		$passwordQuestion->setHidden(true);
		$passwordQuestion->setHiddenFallback(false);

        $hostQuestion = new Question('Host (localhost): ', 'localhost');
        $driverQuestion = new Question('Driver (pdo_mysql): ', 'pdo_mysql');
		
		$user = $helper->ask($input, $output, $userQuestion);
		$password = $helper->ask($input, $output, $passwordQuestion);
		$host = $helper->ask($input, $output, $hostQuestion);
		$driver = $helper->ask($input, $output, $driverQuestion);
		
		$connectionParams = array(
			'user' => $user,
			'password' => $password,
			'host' => $host,
			'driver' => $driver
		);
		
		try {
			$conn = $this->createConnection($connectionParams);
			
			$sm = $conn->getSchemaManager();
			
			$databases = $sm->listDatabases();
			
			$question = new ChoiceQuestion(
				'Database',
				$databases,
				0
			);
			$question->setErrorMessage('Selected database is not valid');

			$dbName = $helper->ask($input, $output, $question);
			$output->writeln('Chosen database: ' . $dbName);
			
			//Add the dbname property
			$connectionParams['dbname'] = $dbName;
			
			$conn = $this->createConnection($connectionParams);
			
			$sm = $conn->getSchemaManager();
		
			$tables = $sm->listTables();
			
			$this->loadConfiguration();
			
			$progress = new ProgressBar($output, count($tables));
			$progress->start();
			
			foreach ($tables as $table) {
				//$output->writeln($table->getName() . " columns:\n\n");
					
				//Columns
				/*
				foreach ($table->getColumns() as $column) {
					//$output->writeln(' - ' . $column->getName() . ': ' . $column->getType() . "\n");
				}
				*/
					
				//Foreign keys
				$name = $table->getName();
				$table = $sm->listTableDetails($name);
				//$foreignKeys = $table->getForeignKeys();
				//$output->writeln("Foreign keys (" . count($foreignKeys) . ")");
				/*
				foreach ($foreignKeys as $foreignKey) {
					//$output->writeln("\n");
					$foreignColumns = $foreignKey->getForeignColumns();
					$localColumns = $foreignKey->getLocalColumns();
					foreach($foreignColumns as $key => $foreignColumn){
						//$output->writeln($foreignKey->getLocalTableName() . "." . $localColumns[$key] . " <=> " . $foreignKey->getForeignTableName() . "." . $foreignColumn . "\n");
					}
				}
				*/
		
				$primaryKeys = $table->getPrimaryKeyColumns();
				//$output->writeln("Primary keys (" . count($primaryKeys) . ")");
				/*
				foreach ($primaryKeys as $primaryKey) {
					//$output->writeln("\n" . $primaryKey);
				}
				*/
				
				//$output->writeln("\n\n");
				$this->generateCode($name, reset($primaryKeys));
				
				$progress->advance();
			}
			
			$progress->finish();
		} catch(\Exception $ex){
			$output->writeln('Generator has found the current exception: ');
			$output->writeln($ex->getMessage());
		}
    }
    
    /**
     * Create a connection with the given connection parameters.
     * @param array $connectionParams An associative array with all the parameters. 
     * @return \Doctrine\DBAL\Connection The connection.
     */
    private function createConnection($connectionParams){
    	$config = new Configuration();
    	return DriverManager::getConnection($connectionParams, $config);
    }
	
	/**
	 * Load the configuration.
	 */
	private function loadConfiguration(){
		$this->config = new Config();
		$this->config->createFromComposer();
	}
    
	/**
	 * Generate the code for the given className and primaryKey and write it in a file.
	 * @param string $className The class name.
	 * @param string $primaryKey The primary key.
	 */
    private function generateCode($className, $primaryKey){
		$tags = $this->config->getTags();
		
		$class    = new ClassGenerator();
		$docblock = DocBlockGenerator::fromArray(array(
			'shortDescription' => ucfirst($className) .' model class',
			'longDescription'  => 'This is a model class generated with DavidePastore\ParisModelGenerator.',
			'tags'             => $tags
		));
		
		$idColumn = new PropertyGenerator('_id_column');
		
		$idColumn
			->setStatic(true)
			->setDefaultValue($primaryKey);
		
		$table = new PropertyGenerator('_table');
		
		$table
			->setStatic(true)
			->setDefaultValue($className);
		
		$class
			->setName(ucfirst($className))
			->setNamespaceName($this->config->getNamespace())
			->setDocblock($docblock)
			->setExtendedClass('Model')
			->addProperties(array(
				$idColumn,
				$table
			));
			
		$file = FileGenerator::fromArray(array(
			'classes'  => array($class),
			'docblock' => DocBlockGenerator::fromArray(array(
				'shortDescription' => ucfirst($className) . ' class file',
				'longDescription'   => null,
				'tags'             => $tags
			))
		));
		
		$generatedCode = $file->generate();
		
		$directory = $this->config->getDestinationFolder() . $this->config->getNamespace();
		
		if(!file_exists($directory)){
			mkdir($directory, 0777, true);
		}
		
		$filePath = $directory . "/" . $class->getName() . ".php";
		
		if(file_exists($filePath) && !$this->force){
			$helper = $this->getHelper('question');
			$realPath = realpath($filePath);
			$this->output->writeln("\n");
			$question = new ConfirmationQuestion('Do you want to overwrite the file "' . $realPath . '"?', false);

			if ($helper->ask($this->input, $this->output, $question)) {
				$this->writeInFile($filePath, $generatedCode);
			}
		}
		else {
			$this->writeInFile($filePath, $generatedCode);
		}
	}
	
	/**
	 * Write the generated code in the file path.
	 * @param string $filePath The path of the file.
	 * @param string $generatedCode The generated code (content to write).
	 */
	private function writeInFile($filePath, $generatedCode){
		//$this->output->writeln('Generated file ' . $filePath);
		file_put_contents($filePath, $generatedCode);
	}
}