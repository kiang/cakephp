<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\ORM\Rule;

use Cake\Datasource\EntityInterface;

/**
 * Checks that a list of fields from an entity are unique in the table
 */
class IsUnique
{

    /**
     * The list of fields to check
     *
     * @var array
     */
    protected $_fields;

    /**
     * Constructor.
     *
     * @param array $fields The list of fields to check uniqueness for
     */
    public function __construct(array $fields)
    {
        $this->_fields = $fields;
    }

    /**
     * Performs the uniqueness check
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity from where to extract the fields
     * @param array $options Options passed to the check,
     * where the `repository` key is required.
     * @return bool
     */
    public function __invoke(EntityInterface $entity, array $options)
    {
        if (!$entity->extract($this->_fields, true)) {
            return true;
        }
        $options += ['allowMultipleNulls' => true];
        $allowMultipleNulls = $options['allowMultipleNulls'];

        $alias = $options['repository']->alias();
        $conditions = $this->_alias($alias, $entity->extract($this->_fields), $allowMultipleNulls);
        if ($entity->isNew() === false) {
            $keys = (array)$options['repository']->primaryKey();
            $keys = $this->_alias($alias, $entity->extract($keys), $allowMultipleNulls);
            if (array_filter($keys, 'strlen')) {
                $conditions['NOT'] = $keys;
            }
        }

        return !$options['repository']->exists($conditions);
    }

    /**
     * Add a model alias to all the keys in a set of conditions.
     *
     * Null values will be omitted from the generated conditions,
     * as SQL UNIQUE indexes treat `NULL != NULL`
     *
     * @param string $alias The alias to add.
     * @param array $conditions The conditions to alias.
     * @param bool $multipleNulls Whether or not to allow multiple nulls.
     * @return array
     */
    protected function _alias($alias, $conditions, $multipleNulls)
    {
        $aliased = [];
        foreach ($conditions as $key => $value) {
            if ($multipleNulls) {
                $aliased["$alias.$key"] = $value;
            } else {
                $aliased["$alias.$key IS"] = $value;
            }
        }
        return $aliased;
    }
}