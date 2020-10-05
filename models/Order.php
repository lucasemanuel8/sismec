<?php

namespace app\models;

use app\components\traits\UpdateCountersTrait;
use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * This is the model class for table "order".
 *
 * @property int $id
 * @property string $code
 * @property float|null $total_value
 * @property string|null $note
 * @property int|null $is_quotation
 * @property string $created_at
 * @property string|null $updated_at
 * @property int $company_id
 *
 * @property Company $company
 * @property OrderItem[] $orderItems
 * @property Sale[] $sales
 */
class Order extends ActiveRecord
{
    use UpdateCountersTrait;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('NOW()'),
            ],
            'SetCode' => [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    self::EVENT_BEFORE_INSERT => 'code',
                ],
                'value' => self::generateCode()
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['company_id'], 'required'],
            [['total_value'], 'number'],
            [['note'], 'string'],
            [['is_quotation', 'company_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['code'], 'string', 'max' => 20],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['company_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'code' => Yii::t('app', 'Code'),
            'total_value' => Yii::t('app', 'Total Value'),
            'note' => Yii::t('app', 'Note'),
            'is_quotation' => Yii::t('app', 'Is Quotation'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'company_id' => Yii::t('app', 'Company ID'),
        ];
    }

    /**
     * Gets query for [[Company]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::class, ['id' => 'company_id']);
    }

    /**
     * Gets query for [[OrderItems]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderItems()
    {
        return $this->hasMany(OrderItem::class, ['order_id' => 'id']);
    }

    /**
     * Gets query for [[Sales]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSales()
    {
        return $this->hasMany(Sale::class, ['order_id' => 'id']);
    }

    private static function generateCode()
    {
        $code = date('YmdHis').substr(microtime(), 2, 6);
        $exists = self::find()->andWhere(['code' => $code])->one();

        return $exists ? self::generateCode() : $code;
    }

    public static function findByCode($code)
    {
        $order = self::find()
            ->andWhere(['code' => $code])
            ->one();

        return $order;        
    }
}
