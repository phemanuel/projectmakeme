<div class="rp-permission-matrix">

    @foreach($modules as $module => $details)
        @php
            $moduleKey = str_replace('.', '-', $module);
            $actions = $details['actions'];
        @endphp

        <div class="rp-module"
             data-rp-module="{{ $module }}">

            <div class="rp-module-header">
                <div>
                    <h4>{{ $details['label'] }}</h4>
                    <p>{{ $details['description'] }}</p>
                </div>

                <label class="rp-module-toggle">
                    <input type="checkbox"
                           data-rp-module-all>
                    <span>All</span>
                </label>
            </div>

            <div class="rp-module-permissions">

                @foreach($actions as $action => $label)
                    @php
                        $permission = "{$module}.{$action}";
                        $inputId = "rp-{$prefix}-{$moduleKey}-{$action}";
                    @endphp

                    <label class="rp-permission-option"
                           for="{{ $inputId }}">

                        <input type="checkbox"
                               id="{{ $inputId }}"
                               name="permissions[]"
                               value="{{ $permission }}"
                               data-rp-permission
                               @checked(
                                   in_array(
                                       $permission,
                                       (array) $selected,
                                       true
                                   )
                               )>

                        <span>{{ $label }}</span>
                    </label>
                @endforeach

            </div>
        </div>
    @endforeach

</div>
