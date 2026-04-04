<?php

declare(strict_types=1);

use Directive\Cli\Validator\AbstractCommandInputValidator;
use Directive\Cli\Validator\CommandInputEntity;
use Directive\Http\Input\SimpleString;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/** Build a configured ArrayInput with argument and option definitions. */
function makeCliInput(
    array $argDefs,
    array $optDefs,
    array $values,
): ArrayInput {
    $defs = [];
    foreach ($argDefs as $name) {
        $defs[] = new InputArgument($name, InputArgument::OPTIONAL);
    }
    foreach ($optDefs as $name) {
        $defs[] = new InputOption($name, null, InputOption::VALUE_OPTIONAL);
    }

    return new ArrayInput($values, new InputDefinition($defs));
}

// ---------------------------------------------------------------------------
// Argument / option hydration
// ---------------------------------------------------------------------------

describe('AbstractCommandInputValidator — hydration', function () {
    it('resolves an argument value into CommandInputEntity', function () {
        $validator = new class extends AbstractCommandInputValidator {
            protected function register(): void
            {
                $this->scalar('name', new SimpleString(), required: true);
            }
        };

        $input = makeCliInput(['name'], [], ['name' => 'Alice']);
        $validator->setInput($input);
        $entity = $validator->getCommandInputEntity();

        expect($validator->hasErrors())->toBeFalse();
        expect($entity->getField('name')?->getCleanedValue())->toBe('Alice');
    });

    it('resolves an option value into CommandInputEntity', function () {
        $validator = new class extends AbstractCommandInputValidator {
            protected function register(): void
            {
                $this->scalar('format', new SimpleString());
            }
        };

        $input = makeCliInput([], ['format'], ['--format' => 'json']);
        $validator->setInput($input);
        $entity = $validator->getCommandInputEntity();

        expect($validator->hasErrors())->toBeFalse();
        expect($entity->getField('format')?->getCleanedValue())->toBe('json');
    });

    it('produces a missing error when a required argument is absent', function () {
        $validator = new class extends AbstractCommandInputValidator {
            protected function register(): void
            {
                $this->scalar('name', new SimpleString(), required: true);
            }
        };

        $input = makeCliInput(['name'], [], []);
        $validator->setInput($input);
        $validator->getCommandInputEntity();

        expect($validator->hasErrors())->toBeTrue();
        expect($validator->getErrors()[0]['type'])->toBe('missing');
        expect($validator->getErrors()[0]['property'])->toBe('name');
    });

    it('does not produce an error when an optional argument is absent', function () {
        $validator = new class extends AbstractCommandInputValidator {
            protected function register(): void
            {
                $this->scalar('name', new SimpleString());
            }
        };

        $input = makeCliInput(['name'], [], []);
        $validator->setInput($input);
        $validator->getCommandInputEntity();

        expect($validator->hasErrors())->toBeFalse();
    });

    it('getCommandInputEntity() returns a CommandInputEntity instance', function () {
        $validator = new class extends AbstractCommandInputValidator {
            protected function register(): void {}
        };

        $validator->setInput(new ArrayInput([]));
        $entity = $validator->getCommandInputEntity();

        expect($entity)->toBeInstanceOf(CommandInputEntity::class);
    });
});

// ---------------------------------------------------------------------------
// setInput() resets state between runs
// ---------------------------------------------------------------------------

describe('AbstractCommandInputValidator — reset on setInput()', function () {
    it('clears errors from a previous validation run', function () {
        $validator = new class extends AbstractCommandInputValidator {
            protected function register(): void
            {
                $this->scalar('name', new SimpleString(), required: true);
            }
        };

        // First run — required field missing → error
        $input = makeCliInput(['name'], [], []);
        $validator->setInput($input);
        $validator->getCommandInputEntity();
        expect($validator->hasErrors())->toBeTrue();

        // Second run — field present → no error
        $input2 = makeCliInput(['name'], [], ['name' => 'Bob']);
        $validator->setInput($input2);
        $validator->getCommandInputEntity();
        expect($validator->hasErrors())->toBeFalse();
    });
});
