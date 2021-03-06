<?php
/**
 * @file BudgetPlanController.php
 *
 * @project bkeeper
 * @author tema 4tema2@gmail.com
 * @date 04.06.14 15:40
 */

class BudgetPlanController extends CController {

    public $sidebar = array();

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer the ID of the model to be loaded
     */
    public function loadModel($id)
    {
        $model=BudgetPlanRecord::model()->findByPk($id);
        if($model===null)
            throw new CHttpException(404,'The requested page does not exist.');
        return $model;
    }

    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id)
    {
        $this->render('view',array(
                'model'=>$this->loadModel($id),
            ));
    }

    public function actionIndex($date=null)
    {
        if (empty($date)) {
            $date = date(BudgetPlan::DATE_FORMAT);
        }
        $records = BudgetPlanRecord::model()->findAll('budget_plan_year='.date('Y', strtotime($date)));
        $expRows = array();
        $comRows = array();
        if (!empty($records)) {
            $bp = new BudgetPlan();
            $bp->date = $date;
            foreach ($records as $record) {
                $bp->budget_plan_id = $record->budget_plan_id;
                switch ($record->article->article_type) {
                    case ArticleEnum::TYPE_EXPENSE:
                        $expRows[] = array(
                            'budget_plan_id' => $record->budget_plan_id,
                            'article_name' => $bp->articleName,
                            'budget_plan_amount' => $bp->expense->plan_amount,
                            'budget_today_amount' => $bp->expense->today_amount,
                        );
                        break;
                    case ArticleEnum::TYPE_COMING:
                        $comRows[$record->budget_plan_id] = array(
                            'budget_plan_id' => $record->budget_plan_id,
                            'article_name' => $bp->articleName,
                            'budget_plan_amount' => $bp->coming->plan_amount,
                            'budget_today_amount' => $bp->coming->today_amount,
                        );
                        break;
                }
            }
        }

        $expDataProvider=new CArrayDataProvider($expRows, array(
            'id'=>'budget_plan_expense',
            'sort'=>array(
                'attributes'=>array(
                    'budget_plan_id', 'article_name'
                ),
            ),
            'pagination'=>array(
                'pageSize'=>40,
            ),
        ));
        $comDataProvider=new CArrayDataProvider($comRows, array(
            'id'=>'budget_plan_coming',
            'sort'=>array(
                'attributes'=>array(
                    'budget_plan_id', 'article_name'
                ),
            ),
            'pagination'=>array(
                'pageSize'=>10,
            ),
        ));


        /*
        $dataProvider=new CActiveDataProvider('BudgetPlanRecord', array(
            'pagination'=>array(
                'pageSize'=>5,
            ),
        ));

        $this->render('index',array(
                'gridDataProvider'=>$dataProvider,
            ));
        // */
        $this->render('index',array(
                'expGridDataProvider'=>$expDataProvider,
                'comGridDataProvider'=>$comDataProvider,
                'month'=>date('m', strtotime($date)),
                'year'=>date('Y', strtotime($date)),
                'overrun'=>BudgetPlan::getTotalOverrun($date),
            ));

    }

    public function actionCreate()
    {
        $model = new BudgetPlanRecord();

        // collect user input data
        if (isset($_POST['BudgetPlanRecord'])) {
            $model->attributes=$_POST['BudgetPlanRecord'];
            if($model->save()) {
                $this->redirect(array('index'));
            }
        }

        $this->render('create', array('model' => $model));
    }

    public function actionUpdate($id)
    {
        $model=$this->loadModel($id);

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if(isset($_POST['BudgetPlanRecord']))
        {
            $model->attributes=$_POST['BudgetPlanRecord'];
            if($model->save()) {
                $this->redirect(array('index'));
            }
        }

        $this->render('update',array(
                'model'=>$model,
            ));
    }

    public function actionDelete($id)
    {
        $model = $this->loadModel($id);
        // collect user input data
        if ($model->delete()) {
            Yii::app()->user->setFlash('agent_msg','<div class="msgbar msg_Success hide_onC"><span class="iconsweet">*</span><p>Агент с ID='.$id.' успешно удален!</p></div>');
            $this->redirect(array('index'));
        }
        else {
            Yii::app()->user->setFlash('agent_msg','<div class="msgbar msg_Error hide_onC"><span class="iconsweet">*</span><p>Ошибка уделания Агента с ID='.$id.'</p></div>');
            $this->redirect(array('index'));
        }
    }

    public function actionApplyOverrun($date=null)
    {
        //Yii::app()->user->setFlash('success',"Нет перерасхода");
        //$this->redirect(array('index'));

        if (empty($date)) {
            $date = date(BudgetPlan::DATE_FORMAT);
        }
        if (!BudgetPlan::getTotalOverrun($date)) {
            Yii::app()->user->setFlash('success',"Нет перерасхода");
            $this->redirect(array('index'));
        }

        $planList = BudgetPlanRecord::model()->findAll('budget_plan_year='.date('Y', strtotime($date)));
        $bp = new BudgetPlan();
        $bp->date = $date;
        $overrunText=array();
        foreach ($planList as $planRecord) {
            $bp->budget_plan_id = $planRecord->budget_plan_id;
            if ($amount=$bp->getOverrun()) {
                $planRecord->setDateAmountAppend($amount, $date, true);
                $success=$planRecord->save();
                if (!$success) {
                    Yii::app()->user->setFlash('error',"Ошибка при сохранении плана #{$bp->budget_plan_id}: ".implode(', ', $bp->getErrors()));
                }
                $overrunText[]=$bp->getArticleName().': '.$amount;
            }
        }
        Yii::app()->user->setFlash('success',"Перерасход составил: ".implode(', ',$overrunText));

        $this->redirect(array('index'));
    }

}
