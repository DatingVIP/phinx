<?php
/**
 * Phinx
 *
 * (The MIT license)
 * Copyright (c) 2014 Rob Morgan
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated * documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @package    Phinx
 * @subpackage Phinx\Console
 */
namespace Phinx\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class Migrate extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->addOption('--environment', '-e', InputArgument::OPTIONAL, 'The target environment');

        $this->setName('migrate')
             ->setDescription('Migrate the database')
             ->addOption('--target', '-t', InputArgument::OPTIONAL, 'The version number to migrate to')
             ->addOption('--databases', '-d', InputArgument::OPTIONAL, 'The name of database(s) which will be used to migrate (separate multiple names with a space)')
             ->setHelp(
<<<EOT
The <info>migrate</info> command runs all available migrations, optionally up to a specific version

<info>phinx migrate -e development</info>
<info>phinx migrate -e development -t 20110103081132</info>
<info>phinx migrate -e development -t 20110103081132 -d m*</info>
<info>phinx migrate -e development -t 20110103081132 -d "m1 m7 m18"</info>
<info>phinx migrate -e development -v</info>

EOT
             );
    }

    /**
     * Migrate the database.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->bootstrap($input, $output);

        $version = $input->getOption('target');
        $environment = $input->getOption('environment');
        $databases = $input->getOption('databases');
        if (!empty(trim($databases))) {
            $databases = explode(' ', $databases);
        }

        if (null === $environment) {
            $environment = $this->getConfig()->getDefaultEnvironment();
            $output->writeln('<comment>warning</comment> no environment specified, defaulting to: ' . $environment);
        } else {
            $output->writeln('<info>using environment</info> ' . $environment);
        }

        $envOptions = $this->getConfig()->getEnvironment($environment);
        $output->writeln('<info>using adapter</info> ' . $envOptions['adapter']);

        $envDatabases = array ();
        $envDatabases = $this->getDatabases($envOptions, $databases);

        // rollback the specified environment
        $start = microtime(true);
        if (!empty($envDatabases)) {
            // run the migrations against all database
            $output->writeln('<info>using database' . (count ( $envDatabases ) > 1 ? 's ' : '') . '</info> ' . implode (', ', $envDatabases));
            foreach ($envDatabases as $database) {
                $output->writeln('');
                $output->writeln('<info>database:</info> ' . $database);
                $this->getManager()->migrate($environment, $database, $version);
            }
        } else {
            $output->writeln('<error>database was not found</error> ');
            $this->getManager()->migrate($environment, null, $version);
        }
        $end = microtime(true);

        $output->writeln('');
        $output->writeln('<comment>All Done. Took ' . sprintf('%.4fs', $end - $start) . '</comment>');
    }
}