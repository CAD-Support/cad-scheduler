<?php
namespace BooklyPro\Lib\Entities;

use Bookly\Lib;

class Tag extends Lib\Base\Entity
{
    const TYPE_CUSTOMER = 'customer';
    const TYPE_SERVICE = 'service';
    const TYPE_EVENT = 'event';

    /** @var string */
    protected $tag;
    /** @var string */
    protected $type;
    /** @var  int */
    protected $attachment_id;
    /** @var  string */
    protected $info;
    /** @var int */
    protected $color_id = 0;

    protected static $table = 'bookly_tags';

    protected static $schema = array(
        'id' => array( 'format' => '%d' ),
        'tag' => array( 'format' => '%s' ),
        'type' => array( 'format' => '%s' ),
        'attachment_id' => array( 'format' => '%d' ),
        'info' => array( 'format' => '%s' ),
        'color_id' => array( 'format' => '%d' ),
    );

    /**************************************************************************
     * Entity Fields Getters & Setters                                        *
     **************************************************************************/

    /**
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param string $tag
     *
     * @return $this
     */
    public function setTag( $tag )
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType( $type )
    {
        $this->type = $type;

        return $this;
    }


    /**
     * @return int
     */
    public function getAttachmentId()
    {
        return $this->attachment_id;
    }

    /**
     * @param int $attachment_id
     */
    public function setAttachmentId( $attachment_id )
    {
        $this->attachment_id = $attachment_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @param string $info
     * @return $this
     */
    public function setInfo( $info )
    {
        $this->info = $info;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getColorId()
    {
        return $this->color_id;
    }

    /**
     * @param int|null $color_id
     *
     * @return $this
     */
    public function setColorId( $color_id )
    {
        $this->color_id = $color_id;

        return $this;
    }

    /**************************************************************************
     * Overridden Methods                                                     *
     **************************************************************************/
}
