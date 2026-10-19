// Shared classes for form controls (Input, Textarea, Select). Keep in one place.

export const controlBase = [
    'block w-full rounded-md border bg-surface text-fg shadow-xs',
    'placeholder:text-fg-subtle',
    'transition-[border-color,box-shadow] duration-150 ease-out',
    'focus:outline-none focus:ring-4',
    'disabled:cursor-not-allowed disabled:bg-surface-2 disabled:text-fg-subtle disabled:opacity-70',
].join(' ');

export const controlState = (invalid) =>
    invalid
        ? 'border-danger-600 focus:border-danger-600 focus:ring-danger-600/15'
        : 'border-border-strong hover:border-fg-subtle/60 focus:border-brand-500 focus:ring-brand-500/15';

export const controlSize = {
    sm: 'h-8 px-2.5 text-13',
    md: 'h-10 px-3 text-sm',
    lg: 'h-12 px-4 text-base',
};

export const labelClass = 'block text-sm font-medium text-fg';
export const hintClass = 'mt-1.5 text-13 text-fg-subtle';
export const errorClass = 'mt-1.5 flex items-start gap-1 text-13 text-danger';
