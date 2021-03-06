<?php
/**
 * Created by PhpStorm.
 * User: tema
 * Date: 18.08.14
 * Time: 19:09
 */

class BudgetPlan extends CModel {

    const DATE_FORMAT='Y-m';

    public $date;
    public $budget_plan_id;

    private $_summaryList;
    private $_summary;

    public function attributeNames()
    {
        return array(
            'date', 'summaryComing', 'summaryExpense', 'budget_plan_id',
            'coming', 'expense', 'articleName',
        );
    }

    public function rules()
    {
        return array(
            array('date', 'default', 'value'=>date(self::DATE_FORMAT)),
            array('date', 'date', 'format'=>'yyyy-MM'),
        );
    }

    public function getArticleName()
    {
        return BudgetPlanRecord::model()->findByPk($this->budget_plan_id)->article->article_name;
    }

    public static function getMonth($date)
    {
        return strtolower(date('M', strtotime($date)));
    }

    public function getSummaryComing()
    {
        return $this->getTotalSummary(ArticleEnum::TYPE_COMING);
    }

    public function getSummaryExpense()
    {
        return $this->getTotalSummary(ArticleEnum::TYPE_EXPENSE);
    }

    public function getTotalSummary($type=ArticleEnum::TYPE_COMING)
    {
        if (empty($this->_summary[$type])) {
            $summaryList = $this->getSummaryList($type);
            $this->_summary[$type] = new BudgetPlanSummaryTotal();
            $this->_summary[$type]->month = self::getMonth($this->date);
            if (!empty($summaryList)) {
                foreach ($summaryList as $summary) {
                    $this->_summary[$type]->total_plan_amount += $summary->plan_amount;
                    $this->_summary[$type]->total_today_amount += $summary->today_amount;
                }
            }
        }
        return $this->_summary[$type];
    }

    /**
     * @return BudgetPlanSummary
     * @throws CException
     */
    public function getExpense()
    {
        if (empty($this->budget_plan_id)) {
            throw new CException("Budget plan ID must be set");
        }
        if (empty($this->_summaryList[ArticleEnum::TYPE_EXPENSE])) {
            $this->getTotalSummary(ArticleEnum::TYPE_EXPENSE);
        }
        return $this->_summaryList[ArticleEnum::TYPE_EXPENSE][$this->budget_plan_id];
    }

    /**
     * @return BudgetPlanSummary
     * @throws CException
     */
    public function getComing()
    {
        if (empty($this->budget_plan_id)) {
            throw new CException("Budget plan ID must be set");
        }
        if (empty($this->_summaryList[ArticleEnum::TYPE_COMING])) {
            $this->getTotalSummary(ArticleEnum::TYPE_COMING);
        }
        return $this->_summaryList[ArticleEnum::TYPE_COMING][$this->budget_plan_id];
    }

    /**
     * @param string $type
     * @return BudgetPlanSummary[]|bool
     */
    public function getSummaryList($type=ArticleEnum::TYPE_COMING)
    {
        if (!$this->validate())
            return false;

        $year = date('Y', strtotime($this->date));
        $month = self::getMonth($this->date);

        if (!empty($this->_summaryList[$type])) {
            return $this->_summaryList[$type];
        }

        $connection = Yii::app()->connMan->getConn('dbMySQL');
        $db_result = $connection->query('getBudgetSummary', array(
                'year' => $year,
                'article_type' => $type,
            ));
        if (!$db_result[0]) {
            return array();
        }

        foreach ($db_result[1] as $summaryInfo) {
            $summary = new BudgetPlanSummary();
            $summary->article_id = $summaryInfo['article_id'];
            $summary->month = $month;
            $summary->plan_amount = $summaryInfo['budget_plan_'.$month];
            $summary->today_amount = Article::model()->findByPk($summaryInfo['article_id'])->getAmount($year, $month);
            $this->_summaryList[$type][$summaryInfo['budget_plan_id']] = $summary;
        }
        return $this->_summaryList[$type];
    }

    public static function getPrevMonthDate($date, $format='Y-m')
    {
        $dt=date_create($date.' first day of last month');
        return $dt->format($format);
    }

    public function getFinishedMonthBalance($date)
    {
        $bFinished = new BudgetPlan();
        $bFinished->date=$date;
        // Возвращаем остаток по счетам на последний день прошлого месяца Y-m-t
        return Account::model()->getTotalBalance(date('Y-m-t', strtotime($date)));
    }

    public function getAmountAtTheEnd()
    {
        $result='---';
        if ($this->date == date('Y-m')) {
            $freeNow=$this->getFinishedMonthBalance($this->date);
            $freeFuture=$this->summaryComing->total_plan_amount - $this->summaryComing->total_today_amount;
            $expense=$this->summaryExpense->total_plan_amount - $this->summaryExpense->total_today_amount;
            $result=$freeNow+$freeFuture-$expense;
        }
        return $result;
    }

    public function getOverrun()
    {
        $amount=0;
        if ($this->expense->today_amount > $this->expense->plan_amount) {
            $amount=$this->expense->today_amount - $this->expense->plan_amount;
        }
        return $amount;
    }

    public static function getTotalOverrun($date=null)
    {
        if (empty($date)) {
            $date = date(BudgetPlan::DATE_FORMAT);
        }
        $planList = BudgetPlanRecord::model()->findAll('budget_plan_year='.date('Y', strtotime($date)));
        if (empty($planList)) {
            return null;
        }
        $bp = new BudgetPlan();
        $bp->date = $date;
        $amount=0;
        foreach ($planList as $planRecord) {
            $bp->budget_plan_id = $planRecord->budget_plan_id;
            $amount+=$bp->getOverrun();
        }
        return $amount;
    }
} 
