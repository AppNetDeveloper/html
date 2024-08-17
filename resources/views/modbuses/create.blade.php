@extends('layouts.admin')
@section('title', __('Create Modbus'))
@section('content')
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('Create Modbus') }}</h4>
                        <form action="{{ route('modbuses.store', $production_line_id) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="name">{{ __('Name') }}</label>
                                <input type="text" class="form-control" id="name" name="name">
                            </div>
                            <div class="form-group">
                                <label for="json_api">{{ __('JSON API VALUE') }}</label>
                                <textarea class="form-control" id="json_api" name="json_api" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="mqtt_topic_modbus">{{ __('MQTT Topic Modbus') }}</label>
                                <input type="text" class="form-control" id="mqtt_topic_modbus" name="mqtt_topic_modbus">
                            </div>
                            <div class="form-group">
                                <label for="mqtt_topic_gross">{{ __('MQTT Topic Gross') }}</label>
                                <input type="text" class="form-control" id="mqtt_topic_gross" name="mqtt_topic_gross">
                            </div>
                            <div class="form-group">
                                <label for="mqtt_topic_control">{{ __('MQTT Topic Control') }}</label>
                                <input type="text" class="form-control" id="mqtt_topic_control" name="mqtt_topic_control">
                            </div>
                            <div class="form-group">
                                <label for="mqtt_topic_boxcontrol">{{ __('MQTT Topic BoxControl') }}</label>
                                <input type="text" class="form-control" id="mqtt_topic_boxcontrol" name="mqtt_topic_boxcontrol">
                            </div>
                            <div class="form-group">
                                <label for="token">{{ __('Token') }}</label>
                                <input type="text" class="form-control" id="token" name="token">
                            </div>
                            <div class="form-group">
                                <label for="dimension_id">{{ __('Dimension ID') }}</label>
                                <input type="text" class="form-control" id="dimension_id" name="dimension_id">
                            </div>
                            <div class="form-group">
                                <label for="dimension">{{ __('Dimension') }}</label>
                                <input type="text" class="form-control" id="dimension" name="dimension">
                            </div>
                            <div class="form-group">
                                <label for="max_kg">{{ __('Max KG') }}</label>
                                <input type="text" class="form-control" id="max_kg" name="max_kg">
                            </div>
                            <div class="form-group">
                                <label for="rep_number">{{ __('Repetition Number') }}</label>
                                <input type="text" class="form-control" id="rep_number" name="rep_number">
                            </div>
                            <div class="form-group">
                                <label for="tara">{{ __('Tara') }}</label>
                                <input type="text" class="form-control" id="tara" name="tara" value="0">
                            </div>
                            <div class="form-group">
                                <label for="tara_calibrate">{{ __('Tara Calibrate') }}</label>
                                <input type="text" class="form-control" id="tara_calibrate" name="tara_calibrate" value="0">
                            </div>
                            <div class="form-group">
                                <label for="min_kg">{{ __('Min KG') }}</label>
                                <input type="text" class="form-control" id="min_kg" name="min_kg">
                            </div>
                            <div class="form-group">
                                <label for="last_kg">{{ __('Last KG') }}</label>
                                <input type="text" class="form-control" id="last_kg" name="last_kg">
                            </div>
                            <div class="form-group">
                                <label for="last_rep">{{ __('Last Repetition') }}</label>
                                <input type="text" class="form-control" id="last_rep" name="last_rep">
                            </div>
                            <div class="form-group">
                                <label for="rec_box">{{ __('Rec Box') }}</label>
                                <input type="text" class="form-control" id="rec_box" name="rec_box">
                            </div>
                            <div class="form-group">
                                <label for="last_value">{{ __('Last Value') }}</label>
                                <input type="text" class="form-control" id="last_value" name="last_value">
                            </div>
                            <div class="form-group">
                                <label for="variacion_number">{{ __('Variation Number') }}</label>
                                <input type="text" class="form-control" id="variacion_number" name="variacion_number">
                            </div>
                            <div class="form-group">
                                <label for="model_name">{{ __('Model Name') }}</label>
                                <select class="form-control" id="model_name" name="model_name" required>
                                    <option value="weight">Weight</option>
                                    <option value="height">Height</option>
                                    <option value="lifeTraficMonitor">Life Traffic Monitor</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="dimension_default">{{ __('Dimension Default') }}</label>
                                <input type="text" class="form-control" id="dimension_default" name="dimension_default">
                            </div>
                            <div class="form-group">
                                <label for="dimension_max">{{ __('Dimension Max') }}</label>
                                <input type="text" class="form-control" id="dimension_max" name="dimension_max">
                            </div>
                            <div class="form-group">
                                <label for="dimension_variacion">{{ __('Dimension Variation') }}</label>
                                <input type="text" class="form-control" id="dimension_variacion" name="dimension_variacion">
                            </div>
                            <div class="form-group">
                                <label for="offset_meter">{{ __('Offset Meter') }}</label>
                                <input type="text" class="form-control" id="offset_meter" name="offset_meter" value="0">
                            </div>
                            <div class="form-group">
                                <label for="printer_id">{{ __('Printer ID') }}</label>
                                <input type="text" class="form-control" id="printer_id" name="printer_id">
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                            <a href="{{ route('modbuses.index', $production_line_id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection