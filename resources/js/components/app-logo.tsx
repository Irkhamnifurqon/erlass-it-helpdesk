export default function AppLogo() {
    return (
        <div className="flex min-w-0 flex-col items-center justify-center py-1">
            <img
                src="/images/erlass-logo.png"
                alt="Erlass Prokreatif Indonesia"
                className="h-10 w-auto max-w-32 object-contain group-data-[collapsible=icon]/sidebar-wrapper:h-8"
            />
            <span className="mt-1 truncate text-[13px] leading-tight font-semibold group-data-[collapsible=icon]/sidebar-wrapper:hidden">
                IT Helpdesk
            </span>
        </div>
    );
}
