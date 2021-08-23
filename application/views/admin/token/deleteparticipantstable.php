<?php
    /**
     * This is the view, when your are trying to delete the survey participants table.
     */
?>
<div class='side-body <?php echo getSideBodyClass(false); ?>'>
    <div class="row welcome survey-action">
        <div class="col-sm-12 content-right">
            <div class="jumbotron message-box message-box-error">
                <h3 style="border-bottom: solid 2px #a15426;">
                    <p class="lead text-warning">
                        <strong>
                            <?php eT("Delete survey participants table"); ?>
                        </strong>
                    </p>
                </h3>
                <p style="margin-top: 50px;">
                    <?php 
                    eT("If you delete this table access codes will no longer be required to access this survey and your survey will switch back to "); 
                    ?>
                    <br>
                    <strong>
                        <?php eT("open-access mode."); ?>
                    </strong>
                    <br> 
                    <br>
                    <?php eT("A backup of this table will be made if you proceed. Your system administrator will be able to access this table.");?>
                    <br>
                    <?php echo '("' . $tableName  . '")'?>
                    <br>
                    <br>
                    <?php eT("If you want to switch back to closed-access mode you only need to initialise the participants table again "); ?>
                    <br>
                    <?php eT("for this survey."); ?>
                    <br>
                    <br>
                </p>
                <button class="btn btn-default btn-lg" type="submit" name="cancel" href="<?php echo $this->createUrl("admin/tokens/sa/index/surveyid/{$iSurveyId}"); ?>">
                    <?php eT("Cancel") ?>
                </button>
                <button class="btn btn-danger btn-lg" type="submit" name="deleteTable" href="<?php echo $this->createUrl("admin/tokens/sa/kill/surveyid/{$iSurveyId}/ok/Y"); ?>">
                    <?php eT("Delete table"); ?>
                </button>
            </div>
        </div>
    </div>
</div>
