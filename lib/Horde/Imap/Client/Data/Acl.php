<?php
/**
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2011-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 */

/**
 * ACL rights for a mailbox (see RFC 2086/4314).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 */
class Horde_Imap_Client_Data_Acl extends Horde_Imap_Client_Data_AclCommon implements ArrayAccess, IteratorAggregate, Serializable
{
    /**
     * ACL rights.
     *
     * @var array
     */
    protected $_rights;

    /**
     * Constructor.
     *
     * @param string $rights  The rights (see RFC 4314 [2.1]).
     */
    public function __construct($rights = '')
    {
        $this->_rights = str_split($rights);
        $this->_normalize();
    }

    /**
     * String representation of the ACL.
     *
     * @return string  String representation (RFC 4314 compliant).
     */
    public function __toString()
    {
        return implode('', $this->_rights);
    }

    /**
     * Computes the difference to another rights string.
     * Virtual rights are ignored.
     *
     * @param string $rights  The rights to compute against.
     *
     * @return array  Two element array: added and removed.
     */
    public function diff($rights)
    {
        $rlist = array_diff(str_split($rights), array_keys($this->_virtual));

        return array(
            'added' => implode('', array_diff($rlist, $this->_rights)),
            'removed' => implode('', array_diff($this->_rights, $rlist))
        );
    }

    /**
     * Normalize virtual rights (see RFC 4314 [2.1.1]).
     */
    protected function _normalize()
    {
        /* Clients conforming to RFC 4314 MUST ignore the virtual ACL_CREATE
         * and ACL_DELETE rights. See RFC 4314 [2.1]. However, we still need
         * to handle these rights when dealing with RFC 2086 servers since
         * we are abstracting out use of ACL_CREATE/ACL_DELETE to their
         * component RFC 4314 rights. */
        foreach ($this->_virtual as $key => $val) {
            foreach ($val as $right) {
                if ($this[$right]) {
                    foreach (array_keys($this->_virtual) as $virtual) {
                        unset($this[$virtual]);
                    }
                    return;
                }
            }
        }
        foreach ($this->_virtual as $key => $val) {
            if ($this[$key]) {
                unset($this[$key]);
                $this->_rights = array_unique(array_merge($this->_rights, $val));
            }
        }
    }

    /* ArrayAccess methods. */

    /**
     */
    public function offsetExists($offset): bool
    {
        return $this[$offset];
    }

    /**
     */
    public function offsetGet($offset): mixed
    {
        return in_array($offset, $this->_rights);
    }

    /**
     */
    public function offsetSet($offset, $value): void
    {
        if ($value) {
            if (!$this[$offset]) {
                $this->_rights[] = $offset;
                $this->_normalize();
            }
        } elseif ($this[$offset]) {
            if (isset($this->_virtual[$offset])) {
                foreach ($this->_virtual[$offset] as $val) {
                    unset($this[$val]);
                }
            }
            unset($this[$offset]);
        }
    }

    /**
     */
    public function offsetUnset($offset): void
    {
        $this->_rights = array_values(array_diff($this->_rights, array($offset)));
    }

    /* IteratorAggregate method. */

    public function getIterator(): \Traversable
    {
        return new ArrayIterator($this->_rights);
    }

    /* Serializable methods. */

    public function __serialize(): array
    {
        return $this->_rights;
    }

    public function __unserialize(array $data): void
    {
        $this->_rights = $data;
    }

    /**
     */
    public function serialize()
    {
        return json_encode($this->_rights);
    }

    /**
     */
    public function unserialize($data)
    {
        $this->_rights = json_decode($data);
    }

}
