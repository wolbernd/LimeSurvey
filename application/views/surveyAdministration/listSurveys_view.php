<?php
/**
* This file render the list of surveys
* It use the Survey model search method to build the data provider.
*
* @var $model  Survey
*/

// DO NOT REMOVE This is for automated testing to validate we see that page
echo viewHelper::getViewTestTag('listSurveys');

?>
<?php $pageSize=Yii::app()->user->getState('pageSize',Yii::app()->params['defaultPageSize']);?>
<div class="ls-space row list-surveys">
    <ul class="nav nav-tabs" id="surveysystem" role="tablist">
        <li class="active"><a href="#surveys"><?php eT('Survey list'); ?></a></li>
        <li><a href="#surveygroups"><?php eT('Survey groups'); ?></a></li>
    </ul>
    <div class="tab-content">
        <div id="surveys" class="tab-pane active">
            <!-- Survey List widget -->
            <?php $this->widget('ext.admin.survey.ListSurveysWidget.ListSurveysWidget', array(
                        'pageSize' => Yii::app()->user->getState('pageSize', Yii::app()->params['defaultPageSize']),
                        'model' => $model
                ));
            ?>
        </div>

        <div id="surveygroups" class="tab-pane">
            <?php
                $this->widget('bootstrap.widgets.TbGridView', array(
                    'dataProvider' => $groupModel->search(),
                    'columns' => $groupModel->columns,
                    'summaryText'=>gT('Displaying {start}-{end} of {count} result(s).').' ',
                    'selectionChanged'=>"function(id){window.location='" . Yii::app()->urlManager->createUrl("admin/surveysgroups/sa/update/id" ) . '/' . "' + $.fn.yiiGridView.getSelection(id.split(',', 1));}",
                ));
            ?>
        </div>
    </div>
</div>
<script>
    $('#surveysystem a').click(function (e) {
        window.location.hash = $(this).attr('href');
        e.preventDefault();
        $(this).tab('show');
        $('.tab-dependent-button').hide();
        $('.tab-dependent-button[data-tab="' + window.location.hash + '"]').show();
    });
    $(document).on('ready pjax:scriptcomplete', function(){
        if(window.location.hash){
            $('#surveysystem').find('a[href='+window.location.hash+']').trigger('click');
        }
    })
</script>
