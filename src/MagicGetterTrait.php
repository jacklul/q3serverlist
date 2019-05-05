<?php
/**
 * This file is part of the q3serverlist package.
 *
 * (c) Jack'lul <jacklulcat@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace jacklul\q3serverlist;

/**
 * This trait allows using camel case getter syntax to retrieve properties and data from entity
 */
trait MagicGetterTrait
{
    /**
     * @param $method
     * @param $args
     *
     * @return mixed|null
     */
    public function __call($method, $args)
    {
        $property = strtolower(ltrim(preg_replace('/[A-Z]/', '_$0', substr($method, 3)), '_'));
        $action = substr($method, 0, 3);
        
        if ($action === 'get') {
            if (isset($this->$property) && $this->$property !== null) {
                return $this->$property;
            }

            if (isset($this->status[$property])) {
                return $this->status[$property];
            }

            if (isset($this->info[$property])) {
                return $this->info[$property];
            }
        }
        
        return null;
    }
}
