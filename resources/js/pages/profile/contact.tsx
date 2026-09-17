import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ContactController from '@/actions/App/Http/Controllers/Profile/ContactController';
import type { ContactDetails } from '@/types';

export default function Contact({ contact }: { contact: ContactDetails }) {
    return (
        <>
            <Head title="Kontaktdaten" />

            <div className="max-w-xl space-y-6">
                <Heading
                    variant="small"
                    title="Kontaktdaten"
                    description="Sie stehen im Kopf von Lebenslauf und Anschreiben."
                />

                <Form
                    {...ContactController.update.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="full_name">Name</Label>
                                <Input
                                    id="full_name"
                                    name="full_name"
                                    defaultValue={contact.full_name}
                                    autoComplete="name"
                                />
                                <InputError message={errors.full_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="street">Straße und Nr.</Label>
                                <Input
                                    id="street"
                                    name="street"
                                    defaultValue={contact.street}
                                    autoComplete="street-address"
                                />
                                <InputError message={errors.street} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-[8rem_1fr]">
                                <div className="grid gap-2">
                                    <Label htmlFor="postal_code">PLZ</Label>
                                    <Input
                                        id="postal_code"
                                        name="postal_code"
                                        defaultValue={contact.postal_code}
                                        autoComplete="postal-code"
                                    />
                                    <InputError message={errors.postal_code} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="city">Ort</Label>
                                    <Input
                                        id="city"
                                        name="city"
                                        defaultValue={contact.city}
                                        autoComplete="address-level2"
                                    />
                                    <InputError message={errors.city} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">Telefon</Label>
                                <Input
                                    id="phone"
                                    name="phone"
                                    defaultValue={contact.phone}
                                    autoComplete="tel"
                                />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">E-Mail</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    defaultValue={contact.email}
                                    autoComplete="email"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <Button type="submit" disabled={processing}>
                                Speichern
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
