<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                aria-hidden="true">&times;</span></button>
    <h4 class="modal-title" id="myModalLabel">
        {{ __('Add Payment') }}</h4>
</div>

<form action="{{route('payment.add', [$invoice->external_id])}}" method="POST" id="paymentForm">
    <div class="modal-body">
        <div class="row">
            <div class="col-lg-12">
                <p class="text-align-center" style="font-size:1.4em; font-weight: 300;">@lang('Amount due')</p>
                <p class="text-align-center lead" style="font-size:2.4em; line-height: 1; font-weight: 300;">{{$amountDueFormatted}}</p>
                <div class="alert alert-danger" id="payment-error" style="display: none;">
                    @lang('Payment amount cannot exceed the amount due')
                </div>
                <hr>
            </div>
            <div class="col-lg-6">
                <div class="form-group col-lg-6 removeleft">
                    <label for="amount" class="thin-weight">@lang('Amount in') {{$amountDue->getCurrency()->getCode()}}</label>
                    <input type="number" step=".01" name="amount" id="amount" value="{{$amountDue->getBigDecimalAmount()}}" class="form-control input-sm">
                </div>
                <div class="form-group col-lg-12 removeleft">
                    <label for="payment_date" class="thin-weight">@lang('Payment date')</label>
                    <input type="date" name="payment_date" id="payment_date" value="{{today()->format(carbonDate())}}" class="form-control input-sm">
                </div>
                <div class="form-group col-lg-12 removeleft">
                    <label for="source" class="thin-weight">@lang('Source')</label>
                    <select name="source" id="source" class="form-control">
                        @foreach($paymentSources as $paymentSource)
                            <option value="{{$paymentSource->getSource()}}">{{$paymentSource->getDisplayValue()}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            {{csrf_field()}}
            <div class="form-group col-lg-12">
                <label for="description" class="thin-weight">@lang('Description')</label>
                <textarea name="description" id="description" class="form-control"></textarea>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default col-lg-6" data-dismiss="modal">{{ __('Close') }}</button>
        <div class="col-lg-6">
            <input type="submit" value="{{__('Register payment')}}" class="btn btn-brand form-control closebtn">
        </div>
    </div>
</form>

@push('scripts')
<script>
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    var amount = parseFloat(document.getElementById('amount').value);
    var amountDue = parseFloat('{{$amountDue->getBigDecimalAmount()}}');
    
    if (amount > amountDue) {
        e.preventDefault();
        document.getElementById('payment-error').style.display = 'block';
        return false;
    }
    document.getElementById('payment-error').style.display = 'none';
    return true;
});

document.getElementById('amount').addEventListener('input', function() {
    var amount = parseFloat(this.value);
    var amountDue = parseFloat('{{$amountDue->getBigDecimalAmount()}}');
    
    if (amount > amountDue) {
        document.getElementById('payment-error').style.display = 'block';
    } else {
        document.getElementById('payment-error').style.display = 'none';
    }
});

        $('#payment_date').pickadate({
            hiddenName:true,
            format: "{{frontendDate()}}",
            formatSubmit: 'yyyy/mm/dd',
            closeOnClear: false,
        });
</script>
@endpush
