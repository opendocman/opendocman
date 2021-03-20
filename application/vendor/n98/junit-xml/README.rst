====================================
JUnit XML Document Generator Library
====================================

.. image:: https://secure.travis-ci.org/cmuench/junit-xml.png
  :target: https://secure.travis-ci.org/cmuench/junit-xml

Install component with composer.

.. code-block:: json

    {
        "require": {
            "n98/junit-xml": "dev-master"
        }
    }


-------
Example
-------

.. code-block:: php

    require_once __DIR__ . '/../vendor/autoload.php';

    $document = new \N98\JUnitXml\Document();

    $suite = $this->document->addTestSuite();
    $timeStamp = new \DateTime();
    $suite->setName('My Test Suite');
    $suite->setTimestamp($timeStamp);
    $suite->setTime(0.344244);

    $testCase = $suite->addTestCase();
    $testCase->addError('My error 1', 'Exception');
    $testCase->addError('My error 2', 'Exception');
    $testCase->addError('My error 3', 'Exception');
    $testCase->addError('My error 4', 'Exception');
    $testCase->addFailure('My failure 1', 'Exception');
    $testCase->addFailure('My failure 2', 'Exception');

    $document->save('results.xml');
