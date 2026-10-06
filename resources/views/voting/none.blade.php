<x-layouts.app title="Voting">
    <x-page-header title="Voting" subtitle="There are no award events open to the public right now." />
    <div class="container-page mt-12">
        <x-empty-state title="No active events" message="Check back soon. Follow us on social media to hear when voting opens.">
            <a href="{{ route('home') }}" class="btn btn-ink">Back to home</a>
        </x-empty-state>
    </div>
</x-layouts.app>
