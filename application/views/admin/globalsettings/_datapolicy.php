<?php
/**
 * @var $sDataPolicy
 * @var $sLegalNotice
 */
?>

<div class="row">
    <div class="col-sm-6">
        <label class="control-label"
               for='showdatapolicybutton'><?php eT("Show data policy on public survey list page:"); ?></label>
        <div>
            <?php $this->widget(
                'yiiwheels.widgets.switch.WhSwitch',
                [
                    'name'        => 'showdatapolicybutton',
                    'htmlOptions' => [
                        'class'        => 'custom-data bootstrap-switch-boolean',
                        'uncheckValue' => false,
                    ],
                    'value'       => isset($sShowGlobalDataPolicyButton) ? $sShowGlobalDataPolicyButton : 0,
                    'onLabel'     => gT('On'),
                    'offLabel'    => gT('Off')
                ]);
            ?>
        </div>
    </div>
    <div class="col-sm-6">
        <label class="control-label"
               for='showlegalnoticebutton'><?php eT("Show legal notice on public survey list page:"); ?></label>
        <div>
            <?php $this->widget(
                'yiiwheels.widgets.switch.WhSwitch',
                [
                    'name'        => 'showlegalnoticebutton',
                    'htmlOptions' => [
                        'class'        => 'custom-data bootstrap-switch-boolean',
                        'uncheckValue' => false,
                    ],
                    'value'       => isset($sShowGlobalLegalNoticeButton) ? $sShowGlobalLegalNoticeButton : 0,
                    'onLabel'     => gT('On'),
                    'offLabel'    => gT('Off')
                ]);
            ?>
        </div>
    </div>
</div>
<div class="ls-space margin top-15">
    <div class="row">
        <div class="col-sm-12 col-lg-6">
            <!-- data policy -->
            <div class="form-group">
                <label class=" control-label"
                       for='datapolicy'><?php eT("Data policy:"); ?></label>
                <div class="">
                    <div class="htmleditor">
                        <?php echo CHtml::textArea("datapolicy", $sDataPolicy, ['class' => 'form-control', 'cols' => '80', 'rows' => '20', 'id' => "datapolicy"]); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-lg-6">
            <!-- legal notice -->
            <div class="form-group">
                <label class=" control-label"
                       for='legalnotice'><?php eT("Legal notice:"); ?></label>
                <div class="">
                    <div class="htmleditor">
                        <?php echo CHtml::textArea("legalnotice", $sLegalNotice, ['class' => 'form-control', 'cols' => '80', 'rows' => '20', 'id' => "legalnotice"]); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    ClassicEditor
        .create(document.querySelector('#datapolicy'))
    ClassicEditor
        .create(document.querySelector('#legalnotice'))
</script>
