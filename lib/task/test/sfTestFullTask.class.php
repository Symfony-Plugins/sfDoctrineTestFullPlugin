<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Launches all tests.
 *
 * @package    symfony
 * @subpackage task
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: sfTestAllTask.class.php 8148 2008-03-29 07:58:59Z fabien $
 */
class sfTestFullTask extends sfBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->aliases = array('test-full');
    $this->namespace = 'test';
    $this->name = 'full';
    $this->briefDescription = 'Launches symfony test, doctrine test, then project tests';

    $this->detailedDescription = <<<EOF
The [test:full|INFO] task launches all unit and functional tests for symfony, doctrine, and project:

  [./symfony test:all|INFO]

The task launches all tests found in [test/|COMMENT].

If one or more test fail, you can try to fix the problem by launching
them by hand or with the [test:unit|COMMENT] and [test:functional|COMMENT] task.
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    require_once(sfConfig::get('sf_symfony_lib_dir').'/vendor/lime/lime.php');

    # Test symfony
    $h = new lime_symfony(new lime_output_color());
    $h->base_dir = realpath(sfConfig::get('sf_symfony_lib_dir').'/../test');

    $h->register(sfFinder::type('file')->prune('fixtures')->name('*Test.php')->in(array_merge(
      // unit tests
      array($h->base_dir.'/unit'),
      glob($h->base_dir.'/../lib/plugins/*/test/unit'),

      // functional tests
      array($h->base_dir.'/functional'),
      glob($h->base_dir.'/../lib/plugins/*/test/functional'),

      // other tests
      array($h->base_dir.'/other')
    )));
    $h->run();

    # Test Doctrine
    chdir(realpath(dirname(__FILE__).'/../../../data/doctrine/tests/'));
    define('DOCTRINE_DIR', dirname(__FILE__).'/../../../../sfDoctrinePlugin/lib/doctrine/');
    require_once (realpath(dirname(__FILE__).'/../../../data/doctrine/tests/run.php'));

    # Test project
    $h = new lime_harness(new lime_output_color());
    $h->base_dir = sfConfig::get('sf_test_dir');

    // register all tests
    $finder = sfFinder::type('file')->follow_link()->name('*Test.php');
    $h->register($finder->in($h->base_dir));

    $h->run();
  }
}

require_once(sfConfig::get('sf_symfony_lib_dir').'/vendor/lime/lime.php');
class lime_symfony extends lime_harness
{
  protected function get_relative_file($file)
  {
    $file = str_replace(DIRECTORY_SEPARATOR, '/', str_replace(array(
      realpath($this->base_dir).DIRECTORY_SEPARATOR,
      realpath($this->base_dir.'/../lib/plugins').DIRECTORY_SEPARATOR,
      $this->extension,
    ), '', $file));

    return preg_replace('#^(.*?)Plugin/test/(unit|functional)/#', '[$1] $2/', $file);
  }
}
