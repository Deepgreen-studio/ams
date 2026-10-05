import { settingsService } from '@/modules/settings/services/settingsService';
import { setAppDateTimeFormats, setAppTimezone } from '@/utils/appTimezone';

/**
 * Load the General Settings timezone, date format, and time format before
 * screens render timestamps.
 */
export function loadApplicationClock() {
    return settingsService.branding()
        .then(({ data }) => {
            const branding = data.data?.branding;
            if (!branding) {
                return;
            }

            setAppTimezone(branding.timezone);
            setAppDateTimeFormats(branding.date_format, branding.time_format);
        })
        .catch(() => {
            // Timestamps stay on UTC when branding cannot be loaded.
        });
}
