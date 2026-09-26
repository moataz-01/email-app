import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Protocol = 'imap' | 'smtp';

export function ServerSettings({
    protocol,
    errors,
    passwordEntryAllowed,
}: {
    protocol: Protocol;
    errors: Record<string, string>;
    passwordEntryAllowed: boolean;
}) {
    const incoming = protocol === 'imap';
    const field = (name: string) => `${protocol}_${name}`;
    return (
        <fieldset className="grid gap-4 rounded-xl border p-5">
            <legend className="px-2 text-sm font-semibold">
                {incoming ? 'Incoming mail · IMAP' : 'Outgoing mail · SMTP'}
            </legend>
            <div className="grid gap-4 sm:grid-cols-[1fr_100px]">
                <div className="grid gap-2">
                    <Label htmlFor={field('host')}>Server hostname</Label>
                    <Input
                        id={field('host')}
                        name={field('host')}
                        placeholder={
                            incoming ? 'imap.example.com' : 'smtp.example.com'
                        }
                        required
                        maxLength={253}
                        aria-invalid={!!errors[field('host')]}
                    />
                    <InputError message={errors[field('host')]} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor={field('port')}>Port</Label>
                    <Input
                        id={field('port')}
                        name={field('port')}
                        type="number"
                        min={1}
                        max={65535}
                        defaultValue={incoming ? 993 : 587}
                        required
                        aria-invalid={!!errors[field('port')]}
                    />
                    <InputError message={errors[field('port')]} />
                </div>
            </div>
            <div className="grid gap-2">
                <Label htmlFor={field('encryption')}>Connection security</Label>
                <select
                    id={field('encryption')}
                    name={field('encryption')}
                    defaultValue={incoming ? 'tls' : 'starttls'}
                    className="bg-background focus-visible:ring-ring h-10 rounded-md border px-3 text-sm focus-visible:ring-2"
                >
                    <option value="tls">SSL/TLS</option>
                    <option value="starttls">STARTTLS</option>
                </select>
                <InputError message={errors[field('encryption')]} />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor={field('username')}>Username</Label>
                    <Input
                        id={field('username')}
                        name={field('username')}
                        autoComplete="off"
                        placeholder="Usually your email address"
                        required
                        maxLength={255}
                        aria-invalid={!!errors[field('username')]}
                    />
                    <InputError message={errors[field('username')]} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor={field('password')}>
                        Password or app password
                    </Label>
                    <Input
                        id={field('password')}
                        name={field('password')}
                        type="password"
                        autoComplete="off"
                        required
                        maxLength={4096}
                        disabled={!passwordEntryAllowed}
                        aria-invalid={!!errors[field('password')]}
                    />
                    <InputError message={errors[field('password')]} />
                </div>
            </div>
        </fieldset>
    );
}
