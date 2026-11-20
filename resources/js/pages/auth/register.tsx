import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';

type Props = {
    passwordRules: string;
    departments: { id: number; name: string }[];
    positions: { id: number; department_id: number; name: string }[];
};

export default function Register({
    passwordRules,
    departments,
    positions,
}: Props) {
    const [departmentId, setDepartmentId] = useState('');
    const departmentPositions = useMemo(
        () =>
            positions.filter(
                (position) => String(position.department_id) === departmentId,
            ),
        [departmentId, positions],
    );
    return (
        <>
            <Head title="Register" />
            <Form
                {...store.form()}
                onSuccess={() => toast.success('Akun berhasil dibuat')}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="department_id">Divisi</Label>
                                <select
                                    id="department_id"
                                    required
                                    name="department_id"
                                    value={departmentId}
                                    onChange={(event) =>
                                        setDepartmentId(event.target.value)
                                    }
                                    className="rounded-md border border-input bg-background px-3 py-2"
                                >
                                    <option value="">Pilih divisi</option>
                                    {departments.map((department) => (
                                        <option
                                            key={department.id}
                                            value={department.id}
                                        >
                                            {department.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.department_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="position_id">Jabatan</Label>
                                <select
                                    id="position_id"
                                    required
                                    name="position_id"
                                    className="rounded-md border border-input bg-background px-3 py-2"
                                >
                                    <option value="">Pilih jabatan</option>
                                    {departmentPositions.map((position) => (
                                        <option
                                            key={position.id}
                                            value={position.id}
                                        >
                                            {position.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.position_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="name"
                                    name="name"
                                    placeholder="Full name"
                                />
                                <InputError
                                    message={errors.name}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={2}
                                    autoComplete="email"
                                    name="email"
                                    placeholder="email@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    tabIndex={3}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder="Password"
                                    passwordrules={passwordRules}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    tabIndex={4}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder="Confirm password"
                                    passwordrules={passwordRules}
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                tabIndex={5}
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                Create account
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Already have an account?{' '}
                            <TextLink
                                href={login()}
                                data-test="login-link"
                                tabIndex={6}
                            >
                                Log in
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Create an account',
    description: 'Enter your details below to create your account',
};
