import { createRoot } from '@wordpress/element';
import Reminders from './Reminders.jsx'

const el = document.getElementById( 'cr_reminders_top_charts' );
const reminderCharts = createRoot( el );
reminderCharts.render( <Reminders nonce={el.getAttribute('data-nonce')} tab={el.getAttribute('data-tab')} /> );
