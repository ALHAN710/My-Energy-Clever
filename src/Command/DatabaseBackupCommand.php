<?php
// src/Command/EndSubscriptionNotificationCommand.php
namespace App\Command;

use Symfony\Component\Process\Process;
use App\Message\UserNotificationMessage;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DatabaseBackupCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:database-backup';

    /**
     * EntityManagerInterface variable
     *
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * Manager Registry variable
     *
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * Project directory path variable
     *
     * @var string
     */
    private $projectDirectory;

    /**
     * SymfonyStyle I/O variable
     *
     * @var SymfonyStyle
     */
    private $io;

    /**
     * MessageBusInterface variable
     *
     * @var MessageBusInterface
     */
    private $messageBus;

    public function __construct(EntityManagerInterface $manager, ManagerRegistry $managerRegistry, string $projectDirectory, MessageBusInterface $messageBus)
    {
        // best practices recommend to call the parent constructor first and
        // then set your own properties. That wouldn't work in this case
        // because configure() needs the properties set in this constructor
        parent::__construct();

        $this->manager = $manager;
        $this->managerRegistry = $managerRegistry;
        $this->projectDirectory = $projectDirectory;
        $this->messageBus = $messageBus;
    }

    protected function configure(): void
    {
        $this->setDescription("Generates a backup of the database in its current state");
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fileSystem = new Filesystem();

        $backupDirectory = "{$this->projectDirectory}/var/backup";

        if ($fileSystem->exists($backupDirectory) === true) {
            $fileSystem->remove($backupDirectory);
        }

        try {
            $fileSystem->mkdir($backupDirectory, 0000);
        } catch (IOException $error) {
            throw new IOException($error);
        }

        //dd($this->managerRegistry->getConnection());

        $databaseConnection = $this->managerRegistry->getConnection();

        [
            'host'     => $databaseHost,
            'port'     => $databasePort,
            'user'     => $databaseUsername,
            'password' => $databasePassword,
            'dbname'   => $databaseName
        ] = $databaseConnection->getParams();

        $currentDatetimeString = (new \DateTimeImmutable('now'))->format('d-m-Y-H-i-s');
        $backupFilePath = "{$backupDirectory}/{$databaseName}-backup-{$currentDatetimeString}.sql";
        $filePathTarget = "--result-file={$backupFilePath}";

        $command = [
            //'C:\wamp64\bin\mysql\mysql8.0.18\bin\mysqldump.exe',
            'mysqldump',
            '--host', // -h
            $databaseHost,
            '--port', // -P
            $databasePort,
            '--user', // -u
            $databaseUsername,
            '-p' . $databasePassword, // -p
            $databaseName,
            '--databases', // If you want to create a CREATE DATABASE statement for an import via PHPMYADMIN for example
            $filePathTarget
        ];

        /*if ($databasePassword === '') {
            $command = [
                //'C:\wamp64\bin\mysql\mysql8.0.18\bin\mysqldump.exe',
                'mysqldump',
                '--host', // -h
                $databaseHost,
                '--port', // -P
                $databasePort,
                '--user', // -u
                $databaseUsername,
                $databaseName,
                '--databases', // If you want to create a CREATE DATABASE statement for an import via PHPMYADMIN for example
                $filePathTarget
            ];
        }*/

        $process = new Process($command);

        $process->setTimeout(90);

        $process->run();

        if ($process->isSuccessful() === false) {
            throw new ProcessFailedException($process);
        }

        $command = [
            'sudo',
            'chown',
            '-R',
            'www-data.www-data',
            "{$this->projectDirectory}/var/backup/",

        ];

        $process = new Process($command);

        $process->setTimeout(90);

        $process->run();

        if ($process->isSuccessful() === false) {
            throw new ProcessFailedException($process);
        }

        $command = [
            'sudo',
            'chmod',
            '-R',
            'a+rw',
            "{$this->projectDirectory}/var/backup/",

        ];

        $process = new Process($command);

        $process->setTimeout(90);

        $process->run();

        if ($process->isSuccessful() === false) {
            throw new ProcessFailedException($process);
        }

        $command = [
            'sudo',
            'chmod',
            'a+x',
            "{$this->projectDirectory}/var/backup/",

        ];

        $process = new Process($command);

        $process->setTimeout(90);

        $process->run();

        if ($process->isSuccessful() === false) {
            throw new ProcessFailedException($process);
        }

        $this->io->success("Backup is generated !");

        if (file_exists($backupFilePath) === true) {
            //"/var/www/KnDFacture-SymWebsite/var/backup/kndfactures-backup-20-12-2021-00-00-02.sql"
            $command = [
                'sudo',
                '/home/debian/sendEmail_DatabaseBackup.sh',
                "{$backupFilePath}",

            ];

            $process = new Process($command);

            $process->setTimeout(90);

            $process->run();

            if ($process->isSuccessful() === false) {
                throw new ProcessFailedException($process);
            }

            //$this->addNotifToQueue($backupFilePath);
        }

        // this method must return an integer number with the "exit status code"
        // of the command. You can also use these constants to make code more readable

        // return this if there was no problem running the command
        // (it's equivalent to returning int(0))
        return Command::SUCCESS;

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;

        // or return this to indicate incorrect command usage; e.g. invalid options
        // or missing arguments (it's equivalent to returning int(2))
        // return Command::INVALID
    }

    /**
     * Permet d'ajouter la Notification à la file d'attente d'envoi de notification SMS/EMAIL
     *
     * @param string $backupFilePath
     * @param bool $isAlert
     * @return void
     */
    private function addNotifToQueue(string $backupFilePath)
    {
        // dd($backupFilePath);
        $str = $backupFilePath;
        // $str = str_replace($this->projectDirectory, "", $backupFilePath);
        //dd($str);
        $object  = 'KnD Factures Database Backup';
        $message = "Le " . date('d/m/Y H:i:s') . " GMT+000

Cher Administrateur,

Ci-joint le fichier de sauvegarde de la base de donnée de l'application web KnD Factures.

Cordialement.

L'équipe KnD Factures";

        //$adminUsers = [];
        $Users = $this->manager->getRepository('App:User')->findAll();
        foreach ($Users as $user) {
            if ($user->getRoles()[0] === 'ROLE_SUPER_ADMIN') {
                //$adminUsers[] = $user;
                $this->messageBus->dispatch(new UserNotificationMessage($user->getId(), $message, 'Email', $object, $str));
            }
        }
    }
}
