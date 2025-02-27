<x-layout>
    <x-form :model="$model">
        <x-card>
            <x-action form="form" />

            <div class="row">
                @bind($model)
                    
                <x-form-input col="6" name="order_id" />
                <x-form-input col="6" name="order_coin" />
                <x-form-input col="6" name="order_category" />
                <x-form-input col="6" name="order_side" />
                <x-form-input col="6" name="order_type" />
                <x-form-input col="6" name="order_qty" />
                <x-form-input col="6" name="order_price" />
                <x-form-input col="6" name="order_reference" />

                @endbind
            </div>

        </x-card>
    </x-form>
</x-layout>
