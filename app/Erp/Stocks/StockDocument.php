<?php

namespace App\Erp\Stocks;

use App\Erp\Contracts\DocumentInterface;
use App\Erp\Organizations\Organization;
use App\Erp\Stocks\Exceptions\StockException;
use Illuminate\Database\Eloquent\Model;

use App\Erp\Organizations\Warehouse;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * Class StockDocument
 * @package App\Erp\Stocks
 */
abstract class StockDocument extends Model implements DocumentInterface
{


    use SoftDeletes;

    /**
     *
     */
    CONST STATUS_NEW = 'new';
    /**
     *
     */
    CONST STATUS_ACTIVATED = 'activated';
    /**
     *
     */
    CONST STATUS_COMPLETE = 'complete';
    /**
     *
     */
    CONST STATUS_CANCELED = 'canceled';


    /**
     * @var array
     */
    protected static $statuses = [
        self::STATUS_NEW,
        self::STATUS_ACTIVATED,
        self::STATUS_COMPLETE,
        self::STATUS_CANCELED,
    ];

    /**
     * @var
     */
    public static $codePrefix;

    /**
     * @var
     */
    public static $itemInstance;


    /**
     * @var array
     */
    protected $attributes = array(

        'weight' => 0,
        'volume' => 0,
        'total' => 0,
        'status' => self::STATUS_NEW

    );

    /**
     * @var array
     */
    protected $with = ['items', 'warehouse'];


    /**
     * Boot the model.
     */
    public static function boot()
    {
        parent::boot();
        static::updating(function($document) {
            $document->warehouseValidate();
        });
    }


    /**
     *
     */
    protected function warehouseValidate()
    {

    }


    /**
     * Документ на основании которого был создан данный
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function reasonable()
    {
        return $this->morphTo();
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items()
    {
        return $this->hasMany(static::$itemInstance);
    }


    /**
     * @return $this
     */
    public function activate()
    {
        $items = $this->items;

        array_walk($items, function($item) {
            $item->activate();

        });

        $this->status = self::STATUS_ACTIVATED;


        return $this;
    }


    /**
     * Закрывает проведенный документ
     * @return $this
     */
    public function complete()
    {
        $items = $this->items;


        array_walk($items, function($item) {
            $item->complete();

        });

        $this->status = self::STATUS_COMPLETE;


        return $this;
    }

    /**
     *
     */
    public function cancel()
    {

    }


    /**
     * @param StockDocument $newDocument
     */
    public function duplicate(StockDocument $newDocument)
    {

    }

    /**
     * Заполняет поля на основании документа
     * @param DocumentInterface $document
     */
    public function populateByDocument(DocumentInterface $document)
    {
        $this->warehouse()->associate($document->getWarehouse());

        $this->documentable()->associate($document);

        $this->code = $document->codeForLinks(static::$codePrefix);

    }


    /**
     * @param $status
     * @return $this
     */
    public function setStatusAttribute($status)
    {
        if (!in_array($status, $this->getAllStatuses()))
            throw new \InvalidArgumentException('неизвестный статус документа');


        $this->attributes['status'] = $status;

        return $this;
    }


    /**
     * @return array
     */
    public function getAllStatuses()
    {
        return static::$statuses;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->status == self::STATUS_NEW;
    }

    /**
     * @return Warehouse
     */
    public function getWarehouse()
    {
        return $this->warehouse;
    }

    /**
     * @return Organization
     */
    public  function getOrganization()
    {
        return $this->getWarehouse()->getOrganization();
    }

    /**
     * @return mixed
     */
    public function getItems()
    {
        return $this->items;
    }




}