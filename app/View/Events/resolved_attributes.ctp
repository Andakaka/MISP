<div class="index">
    <h2><?php echo h($title); ?></h2>
    <?php $scope = !empty($proposals) ? 'proposals' : 'attributes'; ?>
    <p><?php echo __('Below you can see the %s that are to be created. Make sure that the categories and the types are correct, often several options will be offered based on an inconclusive automatic resolution.', $scope);?></p>
    <?php
        if (!empty($missingTldLists)) {
            $missingTldLists = implode(', ', $missingTldLists);
            $missingTldLists = __('Warning: You are missing warninglist(s) that are used to recognise TLDs. Make sure your MISP has the warninglist submodule enabled and updated or else this tool might end up missing valid domains/hostnames/urls. The missing lists are: %s', $missingTldLists);
            echo sprintf('<p class="bold red">%s</p>', $missingTldLists);
        }
    ?>
    <?php
        echo $this->Form->create('MispAttribute', array('url' => $baseurl . '/events/saveFreeText/' . $event['Event']['id'], 'class' => 'mainForm'));
        if ($isSiteAdmin) {
            echo $this->Form->input('Attribute.force', array(
                'checked' => false,
                'label' => __('Proposals instead of attributes'),
            ));
        }
        echo $this->Form->input('Attribute.JsonObject', array(
            'label' => false,
            'type' => 'text',
            'style' => 'display:none;',
            'value' => '',
        ));
        echo $this->Form->input('Attribute.default_comment', array(
            'label' => false,
            'type' => 'text',
            'style' => 'display:none;',
            'value' => $importComment,
        ));
        echo $this->Form->end();
    ?>
    <table class="table table-striped table-hover table-condensed">
        <tr>
            <th><?php echo __('Value');?></th>
            <th><?php echo __('Similar Attributes');?></th>
            <th><?php echo __('Category');?></th>
            <th><?php echo __('Type');?></th>
            <th><?php echo __('IDS');?><input type="checkbox" id="checkAllIDS" style="margin-top:0;margin-left:.3em"></th>
            <th style="text-align:center;"><?php echo __('Disable Correlation');?><input type="checkbox" id="checkAllDC" style="margin-top:0;margin-left:.3em"></th>
            <th><?php echo __('Distribution');?></th>
            <th><?php echo __('Comment');?></th>
            <th><?php echo __('Tags (separated by comma)');?></th>
            <th><?php echo __('Actions');?></th>
        </tr>
        <?php
            $options = array();
            foreach ($resultArray as $k => $item):
        ?>
        <tr data-row="<?= $k ?>" class="freetext_row">
            <?php
                echo $this->Form->input('Attribute' . $k . 'Save', array(
                    'label' => false,
                    'style' => 'display:none;',
                    'value' => 1,
                ));
                echo $this->Form->input('Attribute' . $k . 'Data', array(
                    'label' => false,
                    'type' => 'hidden',
                    'value' => isset($item['data']) ? $item['data'] : false,
                ));
                echo $this->Form->input('Attribute' . $k . 'DataIsHandled', array(
                    'label' => false,
                    'type' => 'hidden',
                    'value' => isset($item['data_is_handled']) ? h($item['data_is_handled']) : false,
                ));
            ?>
            <td>
                <?php
                    echo $this->Form->input('Attribute' . $k . 'Value', array(
                        'label' => false,
                        'value' => $item['value'],
                        'style' => 'width:90%;min-width:400px;',
                        'div' => false
                    ));
                ?>
                <input type="hidden" id="<?php echo 'Attribute' . $k . 'Save'; ?>" value="1">
            </td>
            <td class="shortish">
                <?php
                    foreach (array_slice($item['related'], 0, 10) as $relation):
                        $popover = [
                            __('Event ID') => $relation['Event']['id'],
                            __('Event Info') => $relation['Event']['info'],
                            __('Category') => $relation['Attribute']['category'],
                            __('Type') => $relation['Attribute']['type'],
                            __('Value') => $relation['Attribute']['value'],
                        ];
                        if (!empty($relation['Attribute']['comment'])) {
                            $popover[__('Comment')] = $relation['Attribute']['comment'];
                        }
                        $popoverHTML = '';
                        foreach ($popover as $key => $popoverElement) {
                            $popoverHTML .= '<b>' . $key . '</b>: ' . h($popoverElement) . '<br>';
                        }
                ?>
                        <a href="<?= $baseurl; ?>/events/view/<?= h($relation['Event']['id']) ?>/focus:<?= h($relation['Attribute']['uuid']) ?>" data-toggle="popover" title="<?= __('Attribute details') ?>" data-content="<?php echo h($popoverHTML); ?>" data-trigger="hover"><?= h($relation['Event']['id']) ?></a>
                <?php
                    endforeach;
                    echo count($item['related']) > 10 ? sprintf('<div><i class="muted">%s</i></div>', __('10 +more')) : '';
                ?>
            </td>
            <td class="short">
                <?php
                    if (!isset($item['categories'])) {
                        if (isset($typeDefinitions[$item['default_type']])) {
                            $default = array_search($typeDefinitions[$item['default_type']]['default_category'], $typeCategoryMapping[$item['default_type']]);
                        } else {
                            reset($typeCategoryMapping[$item['default_type']]);
                            $default = key($typeCategoryMapping[$item['default_type']]);
                        }
                    } else {
                        if (isset($item['category_default'])) $default = $item['category_default'];
                        else $default = array_search($item['categories'][0], $typeCategoryMapping[$item['default_type']]);
                    }
                ?>
                <select id="<?php echo 'Attribute' . $k . 'Category'; ?>" style="width: 180px" class="categoryToggle">
                    <?php
                        foreach ($typeCategoryMapping[$item['default_type']] as $category) {
                            if (isset($item['categories']) && !in_array($category, $item['categories'], true)) {
                                continue;
                            }
                            echo '<option';
                            if ($category == $default) echo ' selected';
                            echo '>' . $category . '</option>';
                        }
                    ?>
                </select>
            </td>
            <td class="short">
                <?php
                    $divVisibility = '';
                    $selectVisibility = '';
                    if (count($item['types']) === 1) {
                        $selectVisibility = 'display:none;';
                    } else {
                        $divVisibility = 'style="display:none;"';
                        if (!in_array($item['types'], $options)) {
                            $options[] = $item['types'];
                        }
                    }
                ?>
                <div id="<?php echo 'Attribute' . $k . 'TypeStatic'; ?>" <?php echo $divVisibility; ?> ><?php echo h($item['default_type']); ?></div>
                <select id="<?php echo 'Attribute' . $k . 'Type'; ?>" class='typeToggle' style='<?php echo $selectVisibility; ?>'>
                    <?php
                        if (!empty($item['types'])) {
                            foreach ($item['types'] as $type) {
                                echo '<option';
                                echo ($type === $item['default_type'] ? ' selected' : '') . '>' . h($type) . '</option>';
                            }
                        }
                    ?>
                </select>
            </td>
            <td class="short" style="width:40px;text-align:center;">
                <input type="checkbox" id="<?php echo 'Attribute' . $k . 'To_ids'; ?>" <?php if ($item['to_ids']) echo 'checked'; ?> class="idsCheckbox">
            </td>
            <td class="short" style="width:40px;text-align:center;">
                <input type="checkbox" id="<?php echo 'Attribute' . $k . 'Disable_correlation'; ?>" <?php if (!empty($item['disable_correlation'])) echo 'checked'; ?> class="dcCheckbox">
            </td>
            <td class="short" style="width:40px;text-align:center;">
                <select id="<?php echo 'Attribute' . $k . 'Distribution'; ?>" class='distributionToggle'>
                    <?php
                        foreach ($distributions as $distKey => $distValue) {
                            $default = $item['distribution'] ?? $defaultAttributeDistribution;
                            echo '<option value="' . $distKey . '" ';
                            echo ($distKey == $default ? 'selected="selected"' : '') . '>' . $distValue . '</option>';
                        }
                    ?>
                </select>
                <div style="display:none;">
                    <select id="<?php echo 'Attribute' . $k . 'SharingGroupId'; ?>" class='sgToggle' style='margin-top:3px;'>
                        <?php
                            foreach ($sgs as $sgKey => $sgValue) {
                                echo '<option value="' . h($sgKey) . '">' . h($sgValue) . '</option>';
                            }
                        ?>
                    </select>
                </div>
            </td>
            <td class="short">
                <input type="text" class="freetextCommentField" id="<?php echo 'Attribute' . $k . 'Comment'; ?>" placeholder="<?php echo h($importComment); ?>" <?php if (isset($item['comment']) && $item['comment'] !== false) echo 'value="' . h($item['comment']) . '"'?>>
            </td>
            <td class="short">
                <input type="text" class="freetextTagField" id="<?php echo 'Attribute' . $k . 'Tags'; ?>" <?php if (isset($item['tags']) && $item['tags'] !== false) echo 'value="' . h(implode(",",$item['tags'])) . '"'?>>
            </td>
            <td class="action short">
                <span class="fa fa-times useCursorPointer" title="<?php echo __('Remove resolved attribute');?>" role="button" tabindex="0" aria-label="<?php echo __('Remove resolved attribute');?>" onclick="freetextRemoveRow(<?php echo $k; ?>, <?php echo $event['Event']['id']; ?>);"></span>
            </td>
        </tr>
    <?php
        endforeach;
        $optionsRearranged = array();
        foreach ($options as $group) {
            foreach ($group as $k => $element) {
                $temp = $group;
                unset($temp[$k]);
                if (!isset($optionsRearranged[$element])) $optionsRearranged[$element] = array();
                $optionsRearranged[$element] = array_merge($optionsRearranged[$element], $temp);
            }
        }
    ?>
    </table>
    <span>
        <div class="btn-toolbar" style="float:left;">
            <button class="btn btn-primary" onclick="freetextImportResultsSubmit(<?= $event['Event']['id']; ?>);"><?= __('Submit %s', $scope);?></button>
            <div class="btn-group createObject" style="display: none">
                <?php
                echo $this->Form->create('Object', ['url' => $baseurl . '/objects/createFromFreetext/' . $event['Event']['id'], 'class' => 'hidden']);
                echo $this->Form->input('selectedTemplateId', ['label' => false]);
                echo $this->Form->input('attributes', ['label' => false]);
                echo $this->Form->end();
                ?>
                  <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
                      <?= __('Create object') ?>
                      <span class="caret"></span>
                  </a>
              <ul class="dropdown-menu">
              </ul>
            </div>
        </div>

        <span style="float:right">
            <?php
                if (!empty($optionsRearranged)):
            ?>
                <select id="changeFrom" style="margin-left:50px;margin-top:10px;">
                    <?php
                        foreach (array_keys($optionsRearranged) as $fromElement):
                    ?>
                            <option><?php echo h($fromElement); ?></option>
                    <?php
                        endforeach;
                    ?>
                </select>
                <span class="icon-arrow-right"></span>
                <select id="changeTo" style="margin-top:10px;">
                    <?php
                        $keys = array_keys($optionsRearranged);
                        foreach ($optionsRearranged[$keys[0]] as $toElement):
                    ?>
                            <option><?php echo $toElement; ?></option>
                    <?php
                        endforeach;
                    ?>
                </select>
                <span role="button" tabindex="0" aria-label="<?php echo __('Apply changes to all applicable resolved attributes');?>" title="<?php echo __('Apply changes to all applicable resolved attributes');?>" class="btn btn-inverse" onClick="changeFreetextImportExecute();"><?php echo __('Change all');?></span><br>
            <?php endif; ?>
            <input type="text" id="changeComments" style="margin-left:50px;margin-top:10px;width:446px;" placeholder="<?php echo __('Update all comment fields');?>">
            <span role="button" tabindex="0" aria-label="<?php echo __('Change all');?>" title="<?php echo __('Change all');?>" class="btn btn-inverse" onClick="changeFreetextImportCommentExecute();"><?php echo __('Change all');?></span>
        </span>
    </span>
</div>
    <script>
        var options = <?php echo json_encode($optionsRearranged);?>;
        var typeCategoryMapping = <?php echo json_encode($typeCategoryMapping); ?>;
        $(function() {
            freetextPossibleObjectTemplates();
            popoverStartup();
            $('.typeToggle').on('change', function() {
                var currentId = $(this).attr('id');
                var selected = $(this).val();
                currentId = currentId.replace('Type', 'Category');
                var currentOptions = typeCategoryMapping[selected];

                freetextPossibleObjectTemplates();

                /*
                // Coming soon - restrict further if a list of categories is passed by the modules / freetext import tool
                if ($('#' + currentId)).data('category-restrictions') {
                    var category_restrictions = $('#' + currentId)).data('category-restrictions');
                    currentOptions.forEach(function(category) {
                        var found = False;
                        category_restrictions.forEach(function(restricted_category) {

                        });
                    });
                    currentOptions.forEach() {

                    }
                }
                */
                var $categorySelect = $('#' + currentId);
                $categorySelect.empty();
                for (var category in currentOptions) {
                    $categorySelect.append(new Option(category, category));
                }
            });
        <?php
            if (!empty($optionsRearranged)):
        ?>
                $('#changeFrom').change(function(){
                    changeFreetextImportFrom();
                }).trigger('change');
        <?php
            endif;
        ?>
            $('#checkAllIDS').change(function() {
                $('.idsCheckbox').prop('checked', $(this).is(':checked'));
            });
            $('#checkAllDC').change(function() {
                $('.dcCheckbox').prop('checked', $(this).is(':checked'));
            });
            $('.distributionToggle').change(function() {
                if ($(this).val() == 4) {
                    $(this).next().show();
                } else {
                    $(this).next().hide();
                }
            });
        });
    </script>
<?= $this->element('/genericElements/SideMenu/side_menu', array('menuList' => 'event', 'menuItem' => 'freetextResults'));
