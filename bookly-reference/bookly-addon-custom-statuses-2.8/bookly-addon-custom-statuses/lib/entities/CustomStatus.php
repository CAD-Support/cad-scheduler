<?php
namespace BooklyCustomStatuses\Lib\Entities;

use Bookly\Lib as BooklyLib;

class CustomStatus extends BooklyLib\Base\Entity
{
    /** @var string */
    protected $slug;
    /** @var string */
    protected $name;
    /** @var int */
    protected $busy = 1;
    /** @var string */
    protected $color;
    /** @var int */
    protected $position;

    protected static $table = 'bookly_custom_statuses';

    protected static $schema = array(
        'id' => array( 'format' => '%d' ),
        'slug' => array( 'format' => '%s' ),
        'name' => array( 'format' => '%s' ),
        'busy' => array( 'format' => '%d' ),
        'color' => array( 'format' => '%s' ),
        'position' => array( 'format' => '%d', 'sequent' => true ),
    );

    /**************************************************************************
     * Entity Fields Getters & Setters                                        *
     **************************************************************************/

    /**
     * Gets slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Sets slug
     *
     * @param string $slug
     * @return $this
     */
    public function setSlug( $slug )
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Gets name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets name
     *
     * @param string $name
     * @return $this
     */
    public function setName( $name )
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets busy
     *
     * @return int
     */
    public function getBusy()
    {
        return $this->busy;
    }

    /**
     * Sets busy
     *
     * @param int $busy
     * @return $this
     */
    public function setBusy( $busy )
    {
        $this->busy = $busy;

        return $this;
    }

    /**
     * Gets color
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Sets color
     *
     * @param string $color
     * @return $this
     */
    public function setColor( $color )
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Gets position
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Sets position
     *
     * @param int $position
     * @return $this
     */
    public function setPosition( $position )
    {
        $this->position = $position;

        return $this;
    }

    /**************************************************************************
     * Overridden Methods                                                     *
     **************************************************************************/

    /**
     * @inheritDoc
     */
    public function save()
    {
        if ( $this->isLoaded() ) {
            $modified = $this->getModified();
            if ( array_key_exists( 'slug', $modified ) ) {
                // Update customer_appointment records accordingly.
                BooklyLib\Entities\CustomerAppointment::query()
                    ->update()
                    ->set( 'status', $this->getSlug() )
                    ->where( 'status', $modified['slug'] )
                    ->execute()
                ;
            }
        } elseif( $this->getColor() === null ) {
            $this->setColor( sprintf( '#%06X', mt_rand( 0, 0x64FFFF ) ) );
        }

        return parent::save();
    }

    /**
     * @inheritDoc
     */
    public function delete()
    {
        $default_status = get_option( 'bookly_appointment_default_status' );
        if ( $default_status === $this->getSlug() ) {
            // Restore default status
            update_option( 'bookly_appointment_default_status', 'approved' );
        }

        return parent::delete();
    }
}
