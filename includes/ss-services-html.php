<tr>
    <td colspan="2">
    <strong><?php _e( 'Packages', 'ss-shipstation-rates' ); ?></strong><br/>
        <table>
            <thead>
                <th><?php _e( 'Enable', 'ss-shipstation-rates' ); ?></th>
                <th><?php _e( 'Carrier', 'ss-shipstation-rates' ); ?></th>
                <th><?php _e( 'Method', 'ss-shipstation-rates' ); ?></th>
                <th><?php _e( 'Type(s)', 'ss-shipstation-rates' ); ?></th>
                <th><?php echo sprintf( __( 'Adjustment (%s)', 'ss-shipstation-rates' ), get_woocommerce_currency_symbol() ); ?></th>
                <th><?php _e( 'Adjustment (%)', 'ss-shipstation-rates' ); ?></th>
            </thead>
            <tbody>
                <?php
                    if(empty($this->custom_services)){
                        $this->custom_services = array();
                    }

                    foreach ( $this->services as $code => $services ) {
                        $rowspan = count($services);
                        $i = 1;
                        foreach ( $services as $key => $service ) {
                            if ( ! isset( $this->custom_services[ $code ] ) )
                                $this->custom_services[ $code ] = array();
                            ?>
                            <tr>
                                <?php  if( $i == 1 ): ?>
                                <td rowspan="<?php echo $rowspan; ?>">
                                    <label>
                                        <?php 
                                            $carrier_checked = ( ! empty( $this->custom_services[ $code ]['enabled'] ) ) ? true : false;
                                            if( ! isset( $this->custom_services[ $code ]['enabled'] ) ) $checked = true;
                                            ?>
                                            <input type="checkbox" name="ss_shipping_service[<?php echo $code; ?>][enabled]" <?php checked($carrier_checked , true ); ?> />
                                    </label>
                                </td>
                                <?php endif; ?>
                                <td>
                                    <label><?php echo $service->carrier_name; ?></label>
                                </td>
                                <td>
                                    <input type="text" name="ss_shipping_service[<?php echo $code; ?>][<?php echo $service->code; ?>][name]" placeholder="<?php echo $service->name; ?> (<?php echo $this->title; ?>)" value="<?php echo isset( $this->custom_services[ $code ][ $service->code ]['name'] ) ? $this->custom_services[ $code ][ $service->code ]['name'] : ''; ?>" size="35" />
                                </td>
                                <td>
                                    <ul class="sub_services" style="font-size: 0.92em; color: #555">
                                        <?php foreach ( $service->list_packages as $key => $package ) : ?>
                                        <li style="line-height: 23px;">
                                            <label>
                                                <?php 
                                                $checked = ( ! empty( $this->custom_services[ $code ][ $service->code ][ $package->code ]['enabled'] ) )? true : false;
                                                if(! isset( $this->custom_services[ $code ][ $service->code ][ $package->code ]['enabled'] ) && $package->name  == 'Package') $checked = true;
                                                ?>
                                                <input type="checkbox" name="ss_shipping_service[<?php echo $code; ?>][<?php echo $service->code; ?>][<?php echo $package->code; ?>][enabled]" <?php checked($checked , true ); ?> />
                                                <?php 
                                                echo $package->name; ?>
                                            </label>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td>
                                    <ul class="sub_services" style="font-size: 0.92em; color: #555">
                                        <?php foreach ( $service->list_packages as $key => $package ) : ?>
                                        <li>
                                            <?php echo get_woocommerce_currency_symbol(); ?>
                                            <input type="text" name="ss_shipping_service[<?php echo $code; ?>][<?php echo $service->code; ?>][<?php echo $package->code; ?>][adjustment]" placeholder="N/A" value="<?php echo isset( $this->custom_services[ $code ][ $service->code ][ $package->code ]['adjustment'] ) ? $this->custom_services[ $code ][ $service->code ][ $package->code ]['adjustment'] : ''; ?>" size="4" />
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td>
                                    <ul class="sub_services" style="font-size: 0.92em; color: #555">
                                        <?php foreach ( $service->list_packages as $key => $package ) : ?>
                                        <li>
                                            <input type="text" name="ss_shipping_service[<?php echo $code; ?>][<?php echo $service->code; ?>][<?php echo $package->code; ?>][adjustment_percent]" placeholder="N/A" value="<?php echo isset( $this->custom_services[ $code ][ $service->code ][ $package->code ]['adjustment_percent'] ) ? $this->custom_services[ $code ][ $service->code ][ $package->code ]['adjustment_percent'] : ''; ?>" size="4" />%
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                            </tr>
                        <?php
                            $i++;
                        }
                    }
                ?>
            </tbody>
        </table>
    </td>
</tr>