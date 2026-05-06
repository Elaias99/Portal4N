export default function Sidebar({ userName, canOpenMenu }) {
    return (
        <div className="portal-sidebar-test">
            React OK · {canOpenMenu ? `Menú disponible para ${userName}` : "Sin menú"}
        </div>
    );
}