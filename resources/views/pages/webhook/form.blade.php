<x-layout>
    <x-form :model="$model">
        <x-card>
            <x-action form="form" />

            <div class="row">
                @bind($model)
                    
                <x-form-input col="6" name="webhook_id" />
                <x-form-input col="6" name="webhook_nama" />
                <x-form-input col="6" name="webhook_data" />

                @endbind
            </div>

        </x-card>
    </x-form>
</x-layout>
