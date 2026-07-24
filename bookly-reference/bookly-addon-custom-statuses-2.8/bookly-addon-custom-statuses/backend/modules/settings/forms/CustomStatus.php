<?php
namespace BooklyCustomStatuses\Backend\Modules\Settings\Forms;

use Bookly\Lib as BooklyLib;
use BooklyCustomStatuses\Lib;

class CustomStatus extends BooklyLib\Base\Form
{
    protected static $entity_class = 'CustomStatus';

    protected static $namespace = '\BooklyCustomStatuses\Lib\Entities';

    /**
     * @inheritDoc
     */
    public function configure()
    {
        $this->setFields( array( 'slug', 'name', 'busy' ) );
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        if ( $this->isNew() ) {
            $last = Lib\Entities\CustomStatus::query()->select( 'MAX(position) AS position' )->fetchRow();
            $this->object->setPosition( $last['position'] + 1 );
        }

        return parent::save();
    }
}