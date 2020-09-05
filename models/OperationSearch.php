<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Operation;

/**
 * OperationSearch represents the model behind the search form of `app\models\Operation`.
 */
class OperationSearch extends Operation
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'in_out', 'product_id', 'employee_id'], 'integer'],
            [['amount'], 'number'],
            [['reason', 'created_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Operation::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'in_out' => $this->in_out,
            'amount' => $this->amount,
            'created_at' => $this->created_at,
            'product_id' => $this->product_id,
            'employee_id' => $this->employee_id,
        ]);

        $query->andFilterWhere(['like', 'reason', $this->reason]);

        return $dataProvider;
    }
}
