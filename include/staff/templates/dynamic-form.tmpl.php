<?php
// If the form was removed using the trashcan option, and there was some
// other validation error, don't render the deleted form the second time
if (isset($options['entry']) && $options['mode'] == 'edit'
    && $_POST
    && ($_POST['forms'] && !in_array($options['entry']->getId(), $_POST['forms']))
)
    return;

if (isset($options['entry']) && $options['mode'] == 'edit') { ?>
<tbody>
<?php } ?>
    <tr><td style="width:<?php echo $options['width'] ?: 150;?>px;"></td><td></td></tr>
<?php
// Keep up with the entry id in a hidden field to decide what to add and
// delete when the parent form is submitted
if (isset($options['entry']) && $options['mode'] == 'edit') { ?>
    <input type="hidden" name="forms[]" value="<?php
        echo $options['entry']->getId(); ?>" />
<?php } ?>
<?php if ($form->getTitle()) { ?>
    <tr><th colspan="2" style="font-family:play; color:black;">
        <em><strong><?php echo Format::htmlchars($form->getTitle()); ?></strong>:
        <?php echo Format::htmlchars($form->getInstructions()); ?>
<?php if ($options['mode'] == 'edit') { ?>
        <div class="pull-right">
    <?php if ($options['entry']
                && $options['entry']->getForm()->get('type') == 'G') { ?>
            <a href="#" title="Delete Entry" onclick="javascript:
                $(this).closest('tbody').remove();
                return false;"><i class="icon-trash"></i></a>&nbsp;
    <?php } ?>
            <i class="icon-sort" title="Drag to Sort"></i>
        </div>
<?php } ?></em>
    </th></tr>
    <?php
    }
    
    $vietati=array(34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,
    81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114,115,116,117);//da non visualizzare per equitalia
    
    //$ammessi=array(20,21,22);
    foreach ($form->getFields() as $field) {
        try {
            if (!$field->isVisibleToStaff())
                continue;
        }
        catch (Exception $e) {
            // Not connected to a DynamicFormField
        }
        
        if (in_array($field->get('id'),$vietati))
        continue;
        ?>
        <tr><?php if ($field->isBlockLevel()) { ?>
                <td colspan="2">
                <?php
            }
            else { ?>
                <td class="multi-line <?php if ($field->get('required')) echo 'required';
                ?>" style="min-width:120px;" <?php if ($options['width'])
                    echo "width=\"{$options['width']}\""; ?>>
                <strong style="font-weight:bold; color:black;"><?php echo Format::htmlchars($field->get('label'));?>:</strong></td>
                <td><div style="position:relative"><?php
            }
            $field->render();?>
            <?php if ($field->get('required')) { ?>
                <font class="error">*</font>
            <?php
            }
            if (($a = $field->getAnswer()) && $a->isDeleted()) {
                ?><a class="action-button float-right danger overlay" title="Delete this data"
                    href="#delete-answer"
                    onclick="javascript:if (confirm('<?php echo __('You sure?'); ?>'))
                        $.ajax({
                            url: 'ajax.php/form/answer/'
                                +$(this).data('entryId') + '/' + $(this).data('fieldId'),
                            type: 'delete',
                            success: $.proxy(function() {
                                $(this).closest('tr').fadeOut();
                            }, this)
                        });"
                    data-field-id="<?php echo $field->getAnswer()->get('field_id');
                ?>" data-entry-id="<?php echo $field->getAnswer()->get('entry_id');
                ?>"> <i class="icon-trash"></i> </a></div><?php
            }
            if ($field->get('hint') && !$field->isBlockLevel()) { ?>
                <br /><em style="color:gray;display:inline-block"><?php
                    echo Format::htmlchars($field->get('hint')); ?></em>
            <?php
            }
            foreach ($field->errors() as $e) { ?>
                <br />
                <font class="error"><?php echo Format::htmlchars($e); ?></font>
            <?php } ?>
            </div></td>
        </tr>
    <?php }
if (isset($options['entry']) && $options['mode'] == 'edit') { ?>
</tbody>
<?php } ?>
