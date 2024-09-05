<div class="dsSquadronLocationInfo" style="margin-bottom: 15px;">
    <label for="group-country">Country</label>
    <?php echo ds_get_region_dropdown('dsCountry', false, ds_get_group_meta( '_ds_group_country' ) ) ?> 
    <p>required.</p>
</div>
<div class="dsSquadronLocationInfo" style="margin-bottom: 15px;">
    <label for="group-state">Region</label>
    <select id="dsState" ds-attr-id="<?php echo ds_get_group_meta( '_ds_group_state' ) ?>" name="ds_squadron_state">
        <option value="0">Select Region</option>
    </select>
    
</div>