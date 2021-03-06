<?php

namespace my127\Workspace\Types\Workspace;

use Exception;
use my127\Workspace\Definition\Definition as WorkspaceDefinition;
use my127\Workspace\Definition\Factory as WorkspaceDefinitionFactory;
use ReflectionProperty;

class DefinitionFactory implements WorkspaceDefinitionFactory
{
    const TYPES = ['workspace'];

    /*
     * example
     * -------
     * workspace('name'):
     *   description: An example description here
     *   harness: magento2
     *
     * internal representation
     * -----------------------
     * type: workspace
     * metadata:
     *   path: directory where this definition was loaded from
     * declaration: workspace($name)
     * body:
     *   description: description of the workspace
     *   harness: optional, harness to use for standardising the workspace
     */

    /** @var bool  */
    private $isDefined = false;

    /** @var Definition */
    private $prototype;

    /** @var ReflectionProperty[]  */
    private $properties = [];

    public function __construct()
    {
        $this->prototype = new Definition();

        foreach (['name', 'description', 'harnessName', 'path', 'overlay', 'scope'] as $name) {
            $this->properties[$name] = new ReflectionProperty(Definition::class, $name);
            $this->properties[$name]->setAccessible(true);
        }
    }

    public function create(array $data): WorkspaceDefinition
    {
        if ($this->isDefined) {
            throw new Exception("A workspace has already been declared.");
        }

        $values = [];

        $this->parseMetaData($values, $data['metadata']);
        $this->parseDeclaration($values, $data['declaration']);
        $this->parseBody($values, $data['body']);

        $definition = clone $this->prototype;

        foreach ($this->properties as $name => $property) {
            $property->setValue($definition, $values[$name]);
        }

        $this->isDefined = true;

        return $definition;
    }

    private function parseMetaData(array &$values, $metadata)
    {
        $values['path']  = $metadata['path'];
        $values['scope'] = $metadata['scope'];
    }

    private function parseDeclaration(array &$values, $declaration)
    {
        $values['name'] = substr($declaration, 11, -2);
    }

    private function parseBody(array &$values, $body)
    {
        $values['description'] = $body['description']??null;
        $values['harnessName'] = $body['harness']??null;
        $values['overlay']     = $body['overlay']??null;
    }

    public static function getTypes(): array
    {
        return self::TYPES;
    }
}
