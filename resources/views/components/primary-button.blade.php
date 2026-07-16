<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-shine inline-flex items-center px-4 py-2 bg-gradient-to-r from-teal-600 to-lime-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-md shadow-teal-600/20 hover:from-teal-500 hover:to-lime-500 hover:shadow-lg hover:shadow-teal-600/30 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 active:scale-[0.98] active:translate-y-0 transition-all ease-in-out duration-200']) }}>
    {{ $slot }}
</button>
