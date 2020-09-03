<?php

namespace app\controllers;

use app\models\Category;
use app\models\Product;
use app\models\ProductSearch;
use app\models\VariationAttribute;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\ConflictHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * ProductController implements the CRUD actions for Product model.
 */
class ProductController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'view', 'category', 'create', 'update', 'delete'],
                        'allow' => true,
                        'roles' => ['admin']
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Product models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ProductSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Product model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCategory()
    {
        $model = new Product();
        $list = ArrayHelper::map(Category::find()->all(), 'id', 'name');
        
        if ($model->load(Yii::$app->request->post()))
            return $this->redirect(['create', 'category' => $model->category_id]);
        else if (!Yii::$app->request->isAjax)
            throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));

        return $this->renderAjax('category', [
            'model' => $model,
            'list' => $list
        ]);
    }

    /**
     * Creates a new Product model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($category)
    {
        if (is_null(Category::findOne($category)))
            throw new NotFoundHttpException(Yii::t('app', 'Seleted category not found.'));

        $model = new Product();
        $model->category_id = $category;
        
        if ($model->load(Yii::$app->request->post())) {
            $model->variations = array_filter($model->variations);

            if ($this->modelExists($model))
                throw new ConflictHttpException(Yii::t('app', 'Could not save because the product already exists.'));

            foreach ($model->variations as $var_id)
                (VariationAttribute::findOne($var_id))->link('products', $model);

            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Product model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->loadVariations();

        if ($model->load(Yii::$app->request->post())) {
            $model->variations = array_filter($model->variations);

            if ($this->modelExists($model))
                throw new ConflictHttpException(Yii::t('app', 'Could not save because the product already exists.'));
                
            $model->save();
            $model->unlinkAll('variationAttributes', true);

            foreach ($model->variations as $var_id)
                (VariationAttribute::findOne($var_id))->link('products', $model);

            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Product model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Product model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Product the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Product::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    protected function modelExists($model)
    {
        $query = Product::find()
            ->with('variationAttributes')
            ->andWhere(['product.name' => $model->name])
            ->andWhere(['product.category_id' => $model->category_id])
            ->innerJoin('product_variation_attribute', 'product_variation_attribute.product_id = product.id');

        if (!$model->isNewRecord)
            $query->andWhere(['not', ['product.id' => $model->id]]);

        if (empty($model->variations))
            return $query->one() === null;

        $query = $query->asArray()->all();

        $product_variations = array_map(function ($products) {
            $variation_attributes = $products['variationAttributes'];
            $array = [];
            foreach ($variation_attributes as $variation_attr)
                $array[$variation_attr['variation_set_id']] = $variation_attr['id'];
            return $array;
        }, $query);

        ksort($model->variations);
        foreach ($product_variations as $variations) {
            ksort($variations);
            if (!array_diff($model->variations, $variations))
                return true;
        }

        return false;
    }
}
