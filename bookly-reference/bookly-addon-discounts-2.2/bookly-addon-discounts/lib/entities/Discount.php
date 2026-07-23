<?php
namespace BooklyDiscounts\Lib\Entities;

use Bookly\Lib as BooklyLib;

class Discount extends BooklyLib\Base\Entity
{
    const TYPE_NOP          = 'nop';
    const TYPE_APPOINTMENTS = 'appointments';

    protected static $table = 'bookly_discounts';

    /** @var  string */
    protected $type = self::TYPE_NOP;
    /** @var  string */
    protected $title;
    /** @var  int */
    protected $threshold;
    /** @var float */
    protected $discount = 0;
    /** @var float */
    protected $deduction = 0;
    /** @var string */
    protected $date_start;
    /** @var string */
    protected $date_end;
    /** @var bool */
    protected $enabled = 0;

    protected static $schema = array(
        'id' => array( 'format' => '%d' ),
        'title' => array( 'format' => '%s' ),
        'type' => array( 'format' => '%s' ),
        'threshold' => array( 'format' => '%d' ),
        'discount' => array( 'format' => '%f' ),
        'deduction' => array( 'format' => '%f' ),
        'date_start' => array( 'format' => '%s' ),
        'date_end' => array( 'format' => '%s' ),
        'enabled' => array( 'format' => '%d' ),
    );

    /**
     * Get list of available discounts types
     *
     * @return array
     */
    public static function getTypes()
    {
        return array(
            self::TYPE_NOP => array(
                'title' => __( 'number of persons', 'bookly' ),
                'available' => BooklyLib\Config::groupBookingActive(),
            ),
            self::TYPE_APPOINTMENTS => array(
                'title' => __( 'number of appointments', 'bookly' ),
                'available' => BooklyLib\Config::chainAppointmentsActive() || BooklyLib\Config::multiplyAppointmentsActive() || BooklyLib\Config::recurringAppointmentsActive() || BooklyLib\Config::cartActive(),
            ),
        );
    }

    /**************************************************************************
     * Entity Fields Getters & Setters                                        *
     **************************************************************************/

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle( $title )
    {
        $this->title = $title;

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
    public function getThreshold()
    {
        return $this->threshold;
    }

    /**
     * @param int $threshold
     * @return $this
     */
    public function setThreshold( $threshold )
    {
        $this->threshold = $threshold;

        return $this;
    }

    /**
     * @return float
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * @param float $discount
     * @return $this
     */
    public function setDiscount( $discount )
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * @return float
     */
    public function getDeduction()
    {
        return $this->deduction;
    }

    /**
     * @param float $deduction
     * @return $this
     */
    public function setDeduction( $deduction )
    {
        $this->deduction = $deduction;

        return $this;
    }

    /**
     * @return string
     */
    public function getDateStart()
    {
        return $this->date_start;
    }

    /**
     * @param string $date_start
     * @return Discount
     */
    public function setDateStart( $date_start )
    {
        $this->date_start = $date_start;

        return $this;
    }

    /**
     * @return string
     */
    public function getDateEnd()
    {
        return $this->date_end;
    }

    /**
     * @param string $date_end
     * @return Discount
     */
    public function setDateEnd( $date_end )
    {
        $this->date_end = $date_end;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     * @return Discount
     */
    public function setEnabled( $enabled )
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**************************************************************************
     * Overridden Methods                                                     *
     **************************************************************************/

}
